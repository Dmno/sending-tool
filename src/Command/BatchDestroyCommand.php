<?php

namespace App\Command;

use App\Repository\BatchRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BatchDestroyCommand extends Command
{
    protected static $defaultName = 'batch-destroy';
    /**
     * @var BatchRepository
     */
    private $batchRepository;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var TaskRepository
     */
    private $taskRepository;

    public function __construct(
        BatchRepository $batchRepository,
        EntityManagerInterface $entityManager,
        TaskRepository $taskRepository
    )
    {
        parent::__construct();
        $this->batchRepository = $batchRepository;
        $this->em = $entityManager;
        $this->taskRepository = $taskRepository;
    }

    protected function configure()
    {
        $this->setDescription('Remove old invisible batch records');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $destroyedBatchCount = 0;
        $batches = $this->batchRepository->findBy(['visible' => false]);

        foreach ($batches as $batch) {
            foreach ($batch->getServers() as $server) {
                $server->setCurrentTask(NULL);
                $server->setDead(true);
                $this->em->persist($server);
                $this->em->flush();
            }

            $successDeleting = false;
            $campaignArray = [];
            foreach ($batch->getCampaigns() as $campaign) {
                $campaignArray[] = $campaign;

                $tasks = $this->taskRepository->findBy(['campaign' => $campaign->getId()]);
                foreach ($tasks as $task) {
                    try {
                        $this->em->remove($task);
                    } catch (\Exception $e) {
                        $task->setStatus('sent');
                        $task->setProgress('100');
                        $this->em->persist($task);
                        $this->em->flush();
                    }
                    $successDeleting = true;
                    $this->em->flush();
                }

                if ($successDeleting === true) {
                    foreach ($batch->getServers() as $server) {
                        $this->em->remove($server);
                        $this->em->flush();
                    }

                    foreach ($campaignArray as $camp) {
                        $this->em->remove($camp);
                        $this->em->flush();
                    }
                }
            }

            $this->em->remove($batch);
            $this->em->flush();
            $destroyedBatchCount++;
        }

        if ($destroyedBatchCount > 0) {
            $io->success('Destroyed '.$destroyedBatchCount." batches!");
        } else {
            $io->warning('There are no batches to be destroyed.');
        }

        return 0;
    }
}
