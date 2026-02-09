<?php

namespace App\Controller;

use App\Entity\UserContact;
use App\Repository\UserContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/contact')]
class ContactController extends AbstractController
{
    #[Route('', name: 'app_contact_index', methods: ['GET'])]
    public function index(UserContactRepository $contactRepository): Response
    {
        $user = $this->getUser();

        if ($user) {
            $contacts = $contactRepository->findBy(['user' => $user]);
        } else {
            $contacts = [];
        }

        return $this->render('contact/index.html.twig', [
            'contacts' => $contacts,
        ]);
    }

    #[Route('/new', name: 'app_contact_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->getUser()) {
                $this->addFlash('error', 'Veuillez creer un compte ou vous connecter pour ajouter un contact.');
                return $this->redirectToRoute('app_contact_new');
            }

            $contact = new UserContact();
            $contact->setFirstname($request->request->get('firstname'));
            $contact->setLastname($request->request->get('lastname'));
            $contact->setEmail($request->request->get('email'));
            $contact->setUser($this->getUser());

            $em->persist($contact);
            $em->flush();

            $this->addFlash('success', 'Contact ajoute avec succes!');
            return $this->redirectToRoute('app_contact_index');
        }

        return $this->render('contact/new.html.twig');
    }

    #[Route('/{id}', name: 'app_contact_show', methods: ['GET'])]
    public function show(UserContact $contact): Response
    {
        if ($this->getUser() && $contact->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Vous n\'avez pas acces a ce contact.');
            return $this->redirectToRoute('app_contact_index');
        }

        return $this->render('contact/show.html.twig', [
            'contact' => $contact,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_contact_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, UserContact $contact, EntityManagerInterface $em): Response
    {
        if ($this->getUser() && $contact->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Vous n\'avez pas acces a ce contact.');
            return $this->redirectToRoute('app_contact_index');
        }

        if ($request->isMethod('POST')) {
            if (!$this->getUser()) {
                $this->addFlash('error', 'Veuillez creer un compte ou vous connecter pour modifier un contact.');
                return $this->redirectToRoute('app_contact_edit', ['id' => $contact->getId()]);
            }

            $contact->setFirstname($request->request->get('firstname'));
            $contact->setLastname($request->request->get('lastname'));
            $contact->setEmail($request->request->get('email'));

            $em->flush();

            $this->addFlash('success', 'Contact modifie avec succes!');
            return $this->redirectToRoute('app_contact_index');
        }

        return $this->render('contact/edit.html.twig', [
            'contact' => $contact,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_contact_delete', methods: ['POST'])]
    public function delete(Request $request, UserContact $contact, EntityManagerInterface $em): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('error', 'Veuillez creer un compte ou vous connecter pour supprimer un contact.');
            return $this->redirectToRoute('app_contact_index');
        }

        if ($contact->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Vous n\'avez pas acces a ce contact.');
            return $this->redirectToRoute('app_contact_index');
        }

        $em->remove($contact);
        $em->flush();

        $this->addFlash('success', 'Contact supprime avec succes!');
        return $this->redirectToRoute('app_contact_index');
    }
}
