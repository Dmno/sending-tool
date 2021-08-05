<?php

namespace App\Controller;

use App\Entity\Server;
use App\Repository\ContactListRepository;
use App\Repository\ServerRepository;
use App\Repository\TaskRepository;
use App\Service\ContactFileLoader;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index()
    {
        return $this->redirectToRoute('batch_index');
    }

    /**
     * @Route("test-json-contacts")
     */
    public function testJsonContacts(ServerRepository $serverRepository, EntityManagerInterface $entityManager, TaskRepository $taskRepository, ContactFileLoader $contactFileLoader)
    {
        $client = new Client(['timeout' => 10]);

        /** @var Server[] $servers */
        $servers = $serverRepository->getAliveServers();

//        dd($servers);
        $now = new \DateTime();
        foreach ($servers as $server) {
            $entityManager->refresh($server);
//            echo $server->getIp();
            //check if Server is on cooldown.
            if (($server->getRetry() != 0) && ($server->getRetryAt() > $now)) {
                continue;
            }

//            //update campaign statistics
//            $this->processCampaignSummary($server);

            //check for Task
            if ($server->getCurrentTask() == null) {
                $server->setCurrentTask($taskRepository->getNextTaskByServer($server));
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
                    $contacts = $contactFileLoader->getContacts($import->getContactList(), $import->getOffset(), $import->getLength());

//                    $contacts = [
//                        [
//                            "Email" => 'vladimirfanta@tutanota.com',
//                            'First Name' => "Vladimir",
//                            'Last Name' => 'Fanta'
//                        ],
//                        [
//                            "Email" => 'oliver.pelland@gmail.com',
//                            'First Name' => "Oliver",
//                            'Last Name' => 'Pelland'
//                        ],
//                        [
//                            "Email" => 'rabaciute.ema@gmail.com',
//                            'First Name' => "Ema",
//                            'Last Name' => 'Rabaciute'
//                        ],
//                    ];
//                    dd($contacts);
                    $response = $client->request('POST', "http://".$server->getIp()."/api/startImport.php", ["json" => $contacts]);
                    $data = json_decode($response->getBody()->getContents(), true);
//
                    $import->setListUid($data['id']);
                    $import->setProgress(0);

                    $entityManager->flush();

                    dd($data);
                }
                catch (\Exception $e) {
                    var_dump($e->getMessage());
                    $this->increaseRetryCount($server);
                    continue;
                }
            }
        }
    }

    /**
     * @Route("test-curl-contacts")
     */
    public function testCurlwithserver(ContactListRepository $contactListRepository, ContactFileLoader $contactFileLoader)
    {
        $contactList = $contactListRepository->find(4);

        $data = $contactFileLoader->getContacts($contactList);

//        dd($data);

        $data = json_encode($data);

//        dd($data);

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, "http://81.19.215.165/api/startImport.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json'
            )
        );

        $result = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($result, true);

        dd($response);
    }
}
