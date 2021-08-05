<?php

namespace App\Controller;

use App\Entity\WarmupPlan;
use App\Form\WarmupPlanType;
use App\Repository\WarmupPlanRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/warmup/plan")
 */
class WarmupPlanController extends AbstractController
{
    /**
     * @Route("/", name="warmup_plan_index")
     */
    public function index(WarmupPlanRepository $warmupPlanRepository)
    {
        $warmupPlan = new WarmupPlan();
        $form = $this->createForm(WarmupPlanType::class, $warmupPlan, ['action' => $this->generateUrl('warmup_plan_new')]);
        return $this->render('warmup_plan/index.html.twig', [
            'warmupPlans' => $warmupPlanRepository->getAccessibleWarmupPlans($this->getUser()),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/new", name="warmup_plan_new")
     */
    public function new(Request $request)
    {
        $warmupPlan = new WarmupPlan();
        $form = $this->createForm(WarmupPlanType::class, $warmupPlan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $warmupPlan->setUser($this->getUser());
            $entityManager->persist($warmupPlan);
            $entityManager->flush();

            $this->addFlash('success', 'Warmup plan created successfully!');
        }

        return $this->redirectToRoute('warmup_plan_index');
    }

    /**
     * @Route("/{id}", name="warmup_plan_show", methods={"GET"})
     */
    public function show(WarmupPlan $warmupPlan)
    {
        $form = $this->createForm(WarmupPlanType::class, $warmupPlan, ['action' => $this->generateUrl('warmup_plan_edit', ["id" => $warmupPlan->getId()])]);
        return $this->render('warmup_plan/show.html.twig', [
            'warmupPlan' => $warmupPlan,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="warmup_plan_delete", methods={"DELETE"})
     */
    public function delete(Request $request, WarmupPlan $warmupPlan)
    {
        if ($this->isCsrfTokenValid('delete'.$warmupPlan->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $warmupPlan->setVisible(false);
            $entityManager->flush();
            $this->addFlash('success', 'Warmup plan deleted successfully!');
        }

        return $this->redirectToRoute('warmup_plan_index');
    }

    /**
     * @Route("/{id}/edit", name="warmup_plan_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, WarmupPlan $warmupPlan)
    {
        $form = $this->createForm(WarmupPlanType::class, $warmupPlan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Warmup plan edited successfully!');
        }

        return $this->redirectToRoute('warmup_plan_show', ["id" => $warmupPlan->getId()]);
    }
}
