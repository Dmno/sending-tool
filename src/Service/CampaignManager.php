<?php


namespace App\Service;


use App\Entity\Batch;
use App\Entity\Import;
use App\Entity\Campaign;
use App\Entity\Task;
use App\Entity\WarmupPlan;
use Doctrine\ORM\EntityManagerInterface;

class CampaignManager
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Supports modes: "linear", "simultaneous"
     *
     * Splits ContactList evenly to all Servers and creates one Import for each of them.
     * Creates a specified number of Campaign.
     * Creates Task for each Server and Campaign, assigns the same Import to each Server, as the data sent on each Campaign will be the same.
     *
     * @param Batch $batch
     * @param $hourlyQuota
     * @param $numberOfCampaigns
     */
    public function createCampaign(Batch $batch, $hourlyQuota, $numberOfCampaigns)
    {
        $numberPerServer = floor($batch->getContactList()->getSize() / count($batch->getServers()));

        $campaigns = [];
        for ($i = 0; $i < $numberOfCampaigns; $i++) {
            $campaign = new Campaign();
            $campaign->setSpeed($hourlyQuota);
            $campaign->setBatch($batch);
            $this->entityManager->persist($campaign);
            $campaigns[] = $campaign;
        }

        $i = 1;
        foreach ($batch->getServers() as $server) {
            $import = new Import();
            $import->setContactList($batch->getContactList());
            $import->setOffset($numberPerServer * ($i - 1));
            $import->setLength($numberPerServer);
            $this->entityManager->persist($import);
            foreach ($campaigns as $campaign) {
                $task = new Task();
                $task->setServer($server);
                $task->setCampaign($campaign);
                $task->setImport($import);
                $this->entityManager->persist($task);
            }
            $i++;
        }

        $this->entityManager->flush();
    }

    /**
     * Supports modes: "warmup"
     *
     * Splits ContactList evenly to all Servers.
     * Then it splits contacts again, based on WarmupPlan (number of contacts for each day = HourlyQuota * 24).
     * For each split it creates a new Import.
     * Creates a number of Campaigns, based on WarmupPlan and if there is enough contacts for each day.
     * Creates a Task for each Server and Campaign, assigns specific Import for that one Server + Campaign.
     *
     * @param Batch $batch
     * @param WarmupPlan $warmupPlan
     */
    public function createCampaignFromPlan(Batch $batch, WarmupPlan $warmupPlan)
    {
        $numberPerServer = floor($batch->getContactList()->getSize() / count($batch->getServers()));
        $offset = 0;
        foreach ($warmupPlan->getPlan() as $hourlyQuota) {
            $length = $hourlyQuota * 24;
            if ($offset + $length > $numberPerServer) {
                $length = $numberPerServer - $offset;
            }

            if ($length == 0) {
                break;
            }

            $campaign = new Campaign();
            $campaign->setSpeed($hourlyQuota);
            $campaign->setBatch($batch);
            $this->entityManager->persist($campaign);

            $i = 1;
            foreach ($batch->getServers() as $server) {
                $import = new Import();
                $import->setContactList($batch->getContactList());
                $import->setOffset($numberPerServer * ($i - 1) + $offset);
                $import->setLength($length);
                $this->entityManager->persist($import);

                $task = new Task();
                $task->setServer($server);
                $task->setCampaign($campaign);
                $task->setImport($import);
                $this->entityManager->persist($task);

                $i++;
            }
            $offset += $length;
        }
        $this->entityManager->flush();
    }

    public function resendTask(Task $task)
    {
        $newTask = clone $task;
        $newTask->setImport($task->getImport());
        $newTask->setStatus('waiting');
        $newTask->setProgress(0);
        $newTask->setSent(0);
        $newTask->setOpens(0);
        $newTask->setBounces(0);
        $newTask->setCampaignUid(null);
        $task->setResent(true);
        $this->entityManager->persist($newTask);
        $this->entityManager->flush();

        return $newTask;
    }
}