<?php

namespace App\Controller;

use App\Entity\UserContact;
use App\Repository\UserContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/contacts')]
class ContactController extends AbstractController
{
    #[Route('', name: 'app_contact_index', methods: ['GET'])]
    public function index(UserContactRepository $contactRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Non authentifié'], 401);

        $contacts = $contactRepository->findBy(['user' => $user]);
        return $this->json(array_map(fn($c) => [
            'id' => $c->getId(), 'firstname' => $c->getFirstname(),
            'lastname' => $c->getLastname(), 'email' => $c->getEmail(),
        ], $contacts));
    }

    #[Route('', name: 'app_contact_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Non authentifié'], 401);

        $data = json_decode($request->getContent(), true);
        $contact = new UserContact();
        $contact->setFirstname($data['firstname'] ?? '');
        $contact->setLastname($data['lastname'] ?? '');
        $contact->setEmail($data['email'] ?? '');
        $contact->setUser($user);
        $em->persist($contact);
        $em->flush();

        return $this->json(['id' => $contact->getId()], 201);
    }

    #[Route('/{id}', name: 'app_contact_delete', methods: ['DELETE'])]
    public function delete(UserContact $contact, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user || $contact->getUser() !== $user) return $this->json(['error' => 'Accès refusé'], 403);

        $em->remove($contact);
        $em->flush();
        return $this->json(['message' => 'Supprimé']);
    }
}
