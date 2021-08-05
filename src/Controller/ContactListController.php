<?php

namespace App\Controller;

use App\Entity\ContactList;
use App\Entity\Country;
use App\Form\ContactListEditType;
use App\Form\ContactListType;
use App\Repository\ContactListRepository;
use App\Service\ContactFileLoader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Vich\UploaderBundle\Handler\DownloadHandler;

/**
 * @Route("/contact/list")
 */
class ContactListController extends AbstractController
{
    /**
     * @var ParameterBagInterface
     */
    private $dir;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->dir = $parameterBag->get('kernel.project_dir');
    }

    /**
     * @Route("/", name="contact_list_index", methods={"GET"})
     */
    public function index(Request $request, ContactListRepository $contactListRepository, EntityManagerInterface $entityManager): Response
    {
        $contactList = new ContactList();
        $form = $this->createForm(ContactListType::class, $contactList, ['action' => $this->generateUrl('contact_list_new')]);
        $locales = $entityManager->getRepository(Country::class)->findAll();

        $locale = $request->query->get('locale');

        if (!isset($locale) || empty($locale)) {
            $contactList = $contactListRepository->getAccessibleLists($this->getUser());
        } else {
            $contactList = $contactListRepository->getAccessibleListsByLocale($this->getUser(), $locale);
        }

        return $this->render('contact_list/index.html.twig', [
            'contact_lists' => $contactList,
            'locales' => $locales,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/new", name="contact_list_new", methods={"GET","POST"})
     */
    public function new(Request $request, ContactFileLoader $contactFileLoader): Response
    {
        $contactList = new ContactList();
        $form = $this->createForm(ContactListType::class, $contactList);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $contactList->setUser($this->getUser());
            $contactList->setLocale(substr($form->getData()->getName(), 0, 2));
            $entityManager->persist($contactList);

            $country = $entityManager->getRepository(Country::class)->findOneBy(['title' => substr($form->getData()->getName(), 0, 2)]);
            if ($country === NULL || empty($country)) {
                $country = new Country();
                $country->setTitle(substr($form->getData()->getName(), 0, 2));
                $entityManager->persist($country);
            }
            $entityManager->flush();

            //update Contact List size
            $contactList->setSize($contactFileLoader->getListSize($contactList));
            $entityManager->flush();

            $this->addFlash('success', 'Contact List created successfully!');
        }

        return $this->redirectToRoute('contact_list_index');
    }

    /**
     * @Route("/{id}", name="contact_list_show", methods={"GET"})
     */
    public function show(ContactList $contactList, ContactFileLoader $contactFileLoader): Response
    {
        $contacts = $contactFileLoader->getContacts($contactList, 0, 10);
        $form = $this->createForm(ContactListEditType::class, $contactList, ['action' => $this->generateUrl('contact_list_edit', ["id" => $contactList->getId()])]);
        return $this->render('contact_list/show.html.twig', [
            'contact_list' => $contactList,
            'contacts' => $contacts,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="contact_list_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, ContactList $contactList): Response
    {
        $form = $this->createForm(ContactListEditType::class, $contactList);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'Contact List renamed successfully!');
        }

        return $this->redirectToRoute('contact_list_show', ["id" => $contactList->getId()]);
    }

    /**
     * @Route("/{id}", name="contact_list_delete", methods={"DELETE"})
     */
    public function delete(Request $request, ContactList $contactList): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contactList->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $contactList->setVisible(false);
            $entityManager->flush();
            unlink($this->dir.'/public/files/'.$contactList->getFileName());
            $this->addFlash('success', 'Contact List deleted successfully!');
        }

        return $this->redirectToRoute('contact_list_index');
    }

    /**
     * @Route("/{id}/download", name="contact_list_download", methods={"GET"})
     */
    public function download(ContactList $contactList, DownloadHandler $downloadHandler): Response
    {
        return $downloadHandler->downloadObject($contactList, 'file');
    }
}
