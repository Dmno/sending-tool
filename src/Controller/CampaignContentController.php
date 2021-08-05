<?php

namespace App\Controller;

use App\Entity\Batch;
use App\Entity\CampaignContent;
use App\Entity\Campaign;
use App\Entity\Import;
use App\Entity\Task;
use App\Form\CampaignContentType;
use App\Service\CampaignManager;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\DocBlock\Description;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/campaign/content")
 */
class CampaignContentController extends AbstractController
{
    /**
     * @var CampaignManager
     */
    private $campaignManager;

    public function __construct(CampaignManager $campaignManager)
    {
        $this->campaignManager = $campaignManager;
    }

    /**
     * @Route("/{id}", name="campaign_content_show")
     */
    public function show(CampaignContent $campaignContent)
    {
        return $this->render('campaign_content/show.html.twig', [
            'html' => $campaignContent->getTemplate()
        ]);
    }

    /**
     * Create CampaignContent for all Campaigns in the Batch.
     *
     * @Route("/new/batch/{id}", name="campaign_content_new_batch", methods={"GET", "POST"})
     */
    public function newBatch(Request $request, Batch $batch)
    {
        $campaignContent = new CampaignContent();
        $form = $this->createForm(CampaignContentType::class, $campaignContent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $campaignContent->setTemplate(file_get_contents($form->get('template')->getData()));

            $entityManager->persist($campaignContent);

            foreach ($batch->getCampaigns() as $campaign) {
                $campaign->setCampaignContent($campaignContent);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Campaign content created successfully!');

            return $this->redirectToRoute('batch_show', ['id' => $batch->getId()]);
        }

        return $this->render('campaign_content/new.html.twig', [
            'batch' => $batch,
            'form' => $form->createView()
        ]);
    }

    /**
     * Create CampaignContent only for a specific Campaign.
     *
     * @Route("/new/single/{id}", name="campaign_content_new_single", methods={"GET", "POST"})
     */
    public function newSingle(Request $request, Campaign $campaign)
    {
        $campaignContent = new CampaignContent();
        $form = $this->createForm(CampaignContentType::class, $campaignContent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $campaignContent->setTemplate(file_get_contents($form->get('template')->getData()));

            $entityManager->persist($campaignContent);

            $campaign->setCampaignContent($campaignContent);

            $entityManager->flush();
            $this->addFlash('success', 'Campaign content created successfully!');

            return $this->redirectToRoute('batch_show', ['id' => $campaign->getBatch()->getId()]);
        }

        return $this->render('campaign_content/new.html.twig', [
            'batch' => $campaign->getBatch(),
            'form' => $form->createView()
        ]);
    }

    /**
     * Delete CampaignContent for a specific Campaign.
     *
     * @Route("/{id}/delete", name="campaign_content_delete")
     */
    public function delete(Campaign $campaign)
    {
        $campaign->setCampaignContent(null);
        $this->getDoctrine()->getManager()->flush();
        $this->addFlash('success', 'Campaign content deleted successfully.');

        return $this->redirectToRoute('batch_show', ["id" => $campaign->getBatch()->getId()]);
    }

    /**
     * @Route("/{id}/resend", name="resend_campaign")
     */
    public function resendCampaign(Task $task)
    {
        $newTask = $this->campaignManager->resendTask($task);
        return $this->redirectToRoute('batch_show', ['id' => $newTask->getCampaign()->getBatch()->getId()]);
    }

    /**
     * Resend all task that are able to be resent for this campaign
     *
     * @Route("/{id}/resend/all", name="resend_all_available_tasks")
     */
    public function resendAllAvailableTasks(Campaign $campaign)
    {
        $resentTaskCounter = 0;
        $tasks = $campaign->getTasks();

        foreach ($tasks as $task) {
            if ($task->getStatus() === "sent" && $task->getResent() === false) {
                $newTask = $this->campaignManager->resendTask($task);
                if ($newTask instanceof Task) {
                    $resentTaskCounter++;
                }
            }
        }

        $this->addFlash('success', $resentTaskCounter . ' tasks have been successfully resent');
        return $this->redirectToRoute('batch_show', ['id' => $campaign->getBatch()->getId()]);
    }
}
