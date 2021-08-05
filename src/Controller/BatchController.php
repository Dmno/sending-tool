<?php

namespace App\Controller;

use App\Entity\Batch;
use App\Entity\Task;
use App\Entity\WarmupPlan;
use App\Form\BatchEditType;
use App\Form\BatchType;
use App\Repository\BatchRepository;
use App\Repository\CategoryRepository;
use App\Repository\ContactListRepository;
use App\Repository\ServerRepository;
use App\Repository\TaskRepository;
use App\Repository\WarmupPlanRepository;
use App\Service\CampaignManager;
use App\Service\ServerManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/batch")
 */
class BatchController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var TaskRepository
     */
    private $taskRepository;
    /**
     * @var ServerRepository
     */
    private $serverRepository;

    public function __construct(EntityManagerInterface $entityManager, TaskRepository $taskRepository, ServerRepository $serverRepository)
    {
        $this->em = $entityManager;
        $this->taskRepository = $taskRepository;
        $this->serverRepository = $serverRepository;
    }

    /**
     * @Route("/", name="batch_index", methods={"GET", "POST"})
     */
    public function index(Request $request, BatchRepository $batchRepository, ContactListRepository $contactListRepository, ServerManager $serverManager, CategoryRepository $categoryRepository)
    {
        $batch = new Batch();
        $form = $this->createForm(BatchType::class, $batch, [
            'contactList_choices' => $contactListRepository->getAccessibleLists($this->getUser()),
            'mode_choices' => Batch::$modeChoices
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $batch->setUser($this->getUser());
            $entityManager->persist($batch);
            $entityManager->flush();

            if (!$serverManager->createServersFromList($batch, $form->get('list')->getData())) {
                $this->addFlash('danger', 'Wrong list of servers.');
                return $this->redirectToRoute('batch_index');
            }

            $this->addFlash('success', 'Batch created successfully!');
            return $this->redirectToRoute('batch_show', ['id' => $batch->getId()]);
        }

        return $this->render('batch/index.html.twig', [
            'form' => $form->createView(),
            'batches' => $batchRepository->getBatchesByUser($this->getUser()),
            'categories' => $categoryRepository->findAll()
        ]);
    }

    /**
     * @Route("/{id}", name="batch_show", methods={"GET"})
     */
    public function show(Batch $batch, ContactListRepository $contactListRepository)
    {
        //If Batch setup process is not finished, User is redirected to batch_setup
        if (!$batch->getSetup()) {
            return $this->redirectToRoute('batch_setup', ['id' => $batch->getId()]);
        }
        $form = $this->createForm(BatchEditType::class, $batch, ['action' => $this->generateUrl('batch_edit', ['id' => $batch->getId()])]);
        return $this->render('batch/show.html.twig', [
            'batch' => $batch,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{id}/edit", name="batch_edit")
     */
    public function edit(Request $request, Batch $batch)
    {
        $form = $this->createForm(BatchEditType::class, $batch);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'Batch edited successfully!');
        }

        return $this->redirectToRoute('batch_show', ["id" => $batch->getId()]);
    }

    /**
     * After creating a new Batch, User enters a second step of the setup.
     * If Batch mode is "warmup", User must choose WarmupPlan
     * If Batch mode is not "warmup", User must specify speed and number of campaigns.
     *
     * After completing this step: Imports, Campaigns (without CampaignContent) and Tasks are created.
     *
     * @Route("/{id}/setup", name="batch_setup", methods={"GET", "POST"})
     */
    public function setup(Request $request, Batch $batch, WarmupPlanRepository $warmupPlanRepository, CampaignManager $campaignManager)
    {
        if ($batch->getMode() == "warmup") {
            $choices = $warmupPlanRepository->getAccessibleWarmupPlans($this->getUser());
            $form = $this->createFormBuilder()
                ->add('warmupPlan', EntityType::class, [
                    'label' => 'Warmup Plan',
                    'class' => WarmupPlan::class,
                    'choices' => $choices,
                    'choice_label' => function (WarmupPlan $warmupPlan) {
                        return $warmupPlan->getName()." (".count($warmupPlan->getPlan())." days)";
                    }
                ])
                ->getForm();
            if (count($choices) == 0) {
                return $this->render('batch/setup_warmup_error.html.twig', [
                    'batch' => $batch,
                ]);
            }
        }
        else {
            $form = $this->createFormBuilder()
                ->add('speed', IntegerType::class, [
                    'label' => 'Hourly quota (0 = no limits)',
                    'attr' => [
                        'min' => 0
                    ]
                ])
                ->add('numberOfCampaigns', IntegerType::class, [
                    'label' => 'Number of campaigns',
                    'attr' => [
                        'min' => 1
                    ]
                ])
                ->getForm();
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($batch->getMode() == "warmup") {
                $campaignManager->createCampaignFromPlan($batch, $form->get('warmupPlan')->getData());
            }
            elseif ($batch->getMode() == "simultaneous") {
                $campaignManager->createCampaign($batch, $form->get('speed')->getData(), $form->get('numberOfCampaigns')->getData());
            }
            else {
                $campaignManager->createCampaign($batch, $form->get('speed')->getData(), $form->get('numberOfCampaigns')->getData());
            }

            $batch->setSetup(true);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('batch_show', ['id' => $batch->getId()]);
        }

        return $this->render('batch/setup.html.twig', [
            'batch' => $batch,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{id}", name="batch_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Batch $batch)
    {
        if ($this->isCsrfTokenValid('delete'.$batch->getId(), $request->request->get('_token'))) {
            $batch->setVisible(false);

            //kill servers
            foreach ($batch->getServers() as $server) {
                $server->setDead(true);
            }

            $this->em->flush();

            $this->addFlash('success', 'Batch deleted successfully!');
        }

        return $this->redirectToRoute('batch_index');
    }

    /**
     * Clear every server in the batch and make them brand new.
     *
     * @Route("/{id}/clear", name="batch_clear")
     */
    public function clear(Request $request, Batch $batch, ServerManager $serverManager)
    {
        $serverManager->resetServers($batch);
        $batch->setSetup(0);
        $this->getDoctrine()->getManager()->flush();
        $this->addFlash("success", "Batch has been reset successfully.");

        return $this->redirectToRoute('batch_show', ["id" => $batch->getId()]);
    }

    /**
     * Download bounces
     *
     * @Route("/{id}/download/bounces", name="batch_download_bounces")
     */
    public function downloadBounces(Request $request, Task $task, ServerManager $serverManager)
    {
        $bounces = $serverManager->getBounces($task);

        $rows = [];
        $rows[] = implode(',', ["Email", "First Name", "Last Name"]);
        foreach ($bounces as $bounce) {
            $rows[] = implode(',', $bounce);
        }
        $content = implode("\n", $rows);
        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="bounces.csv"');

        return $response;
    }

    /**
     * Download opens
     *
     * @Route("/{id}/download/opens", name="batch_download_opens")
     */
    public function downloadOpens(Request $request, Task $task, ServerManager $serverManager)
    {
        $opens = $serverManager->getOpens($task);

        $rows = [];
        $rows[] = implode(',', ["Email", "First Name", "Last Name"]);
        foreach ($opens as $open) {
            $rows[] = implode(',', $open);
        }
        $content = implode("\n", $rows);
        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="opens.csv"');

        return $response;
    }

    public function findAndRemoveServer(int $id)
    {
        $server = $this->serverRepository->findOneBy(['id' => $id]);

        $server->setCurrentTask(null);
        if (count($server->getTasks()) !== 0) {
            foreach ($server->getTasks() as $task) {
                $task->setServer(null);
                $this->em->remove($task);
                $this->em->flush();
            }
        }

        $this->em->remove($server);
        $this->em->flush();
        return true;
    }

    /**
     * @Route("/remove/bytask", name="remove_selected_dead_servers_by_task")
     */
    public function removeSelectedDeadServersByTask(Request $request)
    {
        $ids = ($request->request->get('ids'));
        $batchId = ($request->request->get('batch'));
        $deletedServers = 0;
        $serversToDelete = [];

        foreach ($ids as $id) {
            $originalTask = $this->taskRepository->findOneBy(['id' => $id]);
            $foundServer = $originalTask->getServer();

            if (!in_array($foundServer->getId(), $serversToDelete)){
                $serversToDelete[] = $foundServer->getId();
            }
        }

        foreach ($serversToDelete as $serverToDelete) {
            $this->findAndRemoveServer($serverToDelete);
        }

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse(array('response' => $batchId));
        }

        $this->addFlash('success', $deletedServers . ' servers have been deleted');
        return true;
    }

    /**
     * @Route("/remove/server", name="remove_selected_dead_servers")
     */
    public function removeSelectedDeadServers(Request $request)
    {
        $ids = ($request->request->get('ids'));
        $batchId = ($request->request->get('batch'));

        foreach ($ids as $id) {
            $this->findAndRemoveServer($id);
        }

        if ($request->isXMLHttpRequest()) {
            return new JsonResponse(array('response' => $batchId));
        }

        $this->addFlash('success', 'Servers have been deleted');
        return true;
    }
}
