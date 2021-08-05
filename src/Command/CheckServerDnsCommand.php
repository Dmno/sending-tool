<?php

namespace App\Command;

use App\Entity\Server;
use App\Repository\ServerRepository;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This command checks all the new servers and finds if they have been made dead for no reason.
 * If so the server is returned back to working order
 *
 * Class CheckServerDnsCommand
 * @package App\Command
 */
class CheckServerDnsCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var ServerRepository
     */
    private $serverRepository;

    public function __construct(EntityManagerInterface $entityManager, ServerRepository $serverRepository)
    {
        parent::__construct();
        $this->em = $entityManager;
        $this->serverRepository = $serverRepository;
    }

    protected static $defaultName = 'app:check-server-dns';

    protected function configure()
    {
        $this->setDescription('Command that checks newly added servers to avoid unnecessary locks');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $newestServers = $this->serverRepository->getNewestServers();

        /** @var Server $server */
        foreach ($newestServers as $server) {
            $client = new Client(['timeout' => 10]);
            try {
                $response = $client->request('GET', "http://" . $server->getIp() . "/api/campaign.json");
                $responseData = json_decode($response->getBody()->getContents(), true);
            } catch (\Exception $e) {
                return false;
            }

            $server->setDnsCheck(true);
            $server->setRetry(0);
            $server->setDead(false);
            $this->em->flush();
        }

        $io->success('Servers have been checked');
        return 0;
    }
}
