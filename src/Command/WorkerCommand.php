<?php

namespace App\Command;

use App\Entity\Server;
use App\Repository\ServerRepository;
use App\Repository\TaskRepository;
use App\Service\ContactFileLoader;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * * * * * * php /var/www/html/sending-tool/bin/console app:worker
 *
 * Class WorkerCommand
 * @package App\Command
 */
class WorkerCommand extends Command
{
    protected static $defaultName = 'app:worker';

    use LockableTrait;

    private $serverRepository;
    private $taskRepository;
    private $contactFileLoader;
    private $entityManager;

    public function __construct(ServerRepository $serverRepository, TaskRepository $taskRepository, ContactFileLoader $contactFileLoader, EntityManagerInterface $entityManager)
    {
        $this->serverRepository = $serverRepository;
        $this->taskRepository = $taskRepository;
        $this->contactFileLoader = $contactFileLoader;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
    }

    /**
     * If server doesn't respond, the server will be put on "cooldown" for 3 times (for 10, 20 and 30 minutes).
     * If after three attempts Server still doesn't respond, Server becomes "dead"
     *
     * @param Server $server
     * @throws \Exception
     */
    private function increaseRetryCount(Server $server)
    {
        $retryCount = $server->getRetry();
        if ($retryCount == 3) {
            $server->setDead(true);
        }
        else {
            $retryCount++;
            $server->setRetry($retryCount);
            $retryAt = new \DateTime();
            $retryAt->modify('+'.($retryCount * 10).' minutes');
            $server->setRetryAt($retryAt);
        }
        $this->entityManager->flush();
    }

    /**
     * Every Server has campaign.json, which updates frequently with all the campaign information (opens, bounces, sent and total)
     * When WorkerCommand runs, it checks this file in every Server and updates campaign statistics.
     *
     * @param Server $server
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function processCampaignSummary(Server $server)
    {
        $client = new Client(['timeout' => 10]);
        try {
            $response = $client->request('GET', "http://".$server->getIp()."/api/campaign.json");
            $responseData = json_decode($response->getBody()->getContents(), true);
        }
        catch (\Exception $e) {
            $this->increaseRetryCount($server);
            return;
        }
        foreach ($server->getTasks() as $task) {
            if (isset($responseData[$task->getCampaignUid()])) {
                $data = $responseData[$task->getCampaignUid()];
                $task->setProgress(floor($data['sent'] / $data['total'] * 100));
                $task->setOpens($data['opens']);
                $task->setBounces($data['bounces']);
                $task->setSent($data['sent']);
                if ($data['status'] == 'sent') {
                    $task->setStatus('sent');
                    if ($task == $server->getCurrentTask()) {
                        $server->setCurrentTask(null);
                    }
                }
            }
        }
        $this->entityManager->flush();
    }

    /**
     * Every Server can be in 5 states:
     * 1. Newly created, ready for importing (import progress = -1)
     * 2. Importing, import progress between 0 and 99
     * 3. Imported, ready for campaign (import progress = 100)
     * 4. Sending
     * 5. Sent
     *
     * When WorkerCommand runs, for every alive Server it does these things:
     * 1. Checks if Server is on "cooldown"
     * 2. Updates statistics (opens, bounces and etc.)
     * 3. Checks if Server has a Task. If not, it tries to find a new one.
     * 4. If Server has a Task, it checks in what state it is:
     * 4.1. Ready for Import. Starts importing contacts
     * 4.2. Importing. Updates import progress
     * 4.3. Ready for Sending. Starts creating a campaign.
     * If Batch mode is "simultaneous", Server removes currentTask and is ready for another one.
     * If Batch mode is not "simultaneous", Server waits until currentTask campaign progress is 100%.
     *
     * Task is specific to one Server. It holds information about specific Mailwizz campaign in the Server.
     *
     * Campaign unifies all Tasks. It holds CampaignContent and speed, which are the same for all Tasks.
     *
     * CampaignContent holds "fromName", "subject" and "template". Many Campaigns can have the same CampaignContent.
     *
     * Import is specific to one Server. It holds information about what part of ContactList to import to the server,
     * and also holds the progress of the import process. Import can be shared between Campaigns.
     * If all Campaigns are set to send to the same contacts, Import will be the same and data will be imported to the Server only once.
     * If Campaigns are set to send to different contacts for every Campaign, Import will be created for each Campaign.
     *
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');

            return 0;
        }
        $client = new Client(['timeout' => 10]);

        /** @var Server[] $servers */
        $servers = $this->serverRepository->getAliveServers();
        $now = new \DateTime();
        foreach ($servers as $server) {
            $this->entityManager->refresh($server);

            //check if Server is on cooldown.
            if (($server->getRetry() != 0) && ($server->getRetryAt() > $now)) {
                continue;
            }

            //update campaign statistics
            $this->processCampaignSummary($server);

            //check for Task
            if ($server->getCurrentTask() == null) {
                $server->setCurrentTask($this->taskRepository->getNextTaskByServer($server));
            }

            $task = $server->getCurrentTask();

            if ($task == null) {
                continue;
            }

            $import = $task->getImport();
            $campaign = $task->getCampaign();
            $campaignContent = $campaign->getCampaignContent();

            //1: try to import
            if ($import->getProgress() == -1) {
                try {
                    $contacts = $this->contactFileLoader->getContacts($import->getContactList(), $import->getOffset(), $import->getLength());
                    $response = $client->request('POST', "http://".$server->getIp()."/api/startImport.php", ["json" => $contacts]);
                    $data = json_decode($response->getBody()->getContents(), true);
                    $import->setListUid($data['id']);
                    $import->setProgress(0);
                }
                catch (\Exception $e) {
                    $this->increaseRetryCount($server);
                    continue;
                }
            }

            //2: check import status
            else if ($import->getProgress() != 100) {
                try {
                    $response = $client->request('GET', "http://".$server->getIp()."/api/import.json");
                    $data = json_decode($response->getBody()->getContents(), true);
                    if ($server->getCurrentTask()->getImport()->getListUid() == $data['listUid']) {
                        $server->getCurrentTask()->getImport()->setProgress($data['progress']);
                    }
                }
                catch (\Exception $e) {
                    $this->increaseRetryCount($server);
                    continue;
                }
            }

            //3: try to create campaign
            else if (($import->getProgress() == 100) && ($task->getStatus() != 'sending') && ($campaignContent != null)) {
                try {
                    $request = [
                        "from_name" => $campaignContent->getFromName(),
                        "subject" => $campaignContent->getSubjectLine(),
                        "template" => $campaignContent->getTemplate(),
                        "list_uid" => $task->getImport()->getListUid(),
                        "hourly_quota" => $campaign->getSpeed(),
                    ];
                    $response = $client->request('POST', "http://".$server->getIp()."/api/startCampaign.php", ["json" => $request]);
                    $data = json_decode($response->getBody()->getContents(), true);
                    if ($data['status'] == 'success') {
                        $task->setCampaignUid($data['campaignUid']);
                        $task->setStatus('sending');
                        if ($server->getBatch()->getMode() == 'simultaneous') {
                            $server->setCurrentTask(null);
                        }
                    }
                }
                catch (\Exception $e) {
                    $this->increaseRetryCount($server);
                    continue;
                }
            }

            $server->setRetry(0);
            $this->entityManager->flush();
        }
    }
}
