<?php

namespace App\Service;

use App\Entity\Batch;
use App\Entity\Server;
use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;

class ServerManager
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Creates Server entities, based on the list submitted while creating a Batch
     *
     * @param Batch $batch
     * @param $data
     * @return bool
     */
    public function createServersFromList(Batch $batch, $data)
    {
        $lines = explode("\r\n", $data);
        foreach ($lines as $line) {
            $row = str_getcsv($line, "\t");
            if (ip2long($row[0]) === false) {
                return false;
            }
            $newServer = new Server($row[0], $batch);
            $newServer->setCreatedAt(new \DateTime());
            $this->entityManager->persist($newServer);
        }

        $this->entityManager->flush();

        return true;
    }

    /**
     * Access a script on every Server, that deletes all the data on the server and makes them brand new.
     *
     * @param Batch $batch
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function resetServers(Batch $batch)
    {
        $client = new Client(['timeout' => 10]);
        foreach ($batch->getServers() as $server) {
            try {
                $client->request('GET', "http://".$server->getIp()."/api/clear.php");
            }
            catch (\Exception $e) {

            }

            $server->setCurrentTask(null);
            foreach ($server->getTasks() as $task) {
                $this->entityManager->remove($task);
            }
            foreach ($server->getBatch()->getCampaigns() as $campaign) {
                $this->entityManager->remove($campaign);
            }
        }
        $this->entityManager->flush();
    }

    public function getBounces(Task $task)
    {
        $client = new Client(['timeout' => 10]);
        try {
            $response = $client->request('GET', "http://".$task->getServer()->getIp()."/api/download.php?type=bounces&list=".$task->getImport()->getListUid()."&campaign=".$task->getCampaignUid());
            return json_decode($response->getBody()->getContents(), true);
        }
        catch (\Exception $e) {

        }
    }

    public function getOpens(Task $task)
    {
        $client = new Client(['timeout' => 10]);
        try {
            $response = $client->request('GET', "http://".$task->getServer()->getIp()."/api/download.php?type=opens&list=".$task->getImport()->getListUid()."&campaign=".$task->getCampaignUid());
            return json_decode($response->getBody()->getContents(), true);
        }
        catch (\Exception $e) {

        }
    }
}