<?php

namespace App\Controller;

use App\Entity\Generation;
use App\Repository\GenerationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/generation')]
class GenerationController extends AbstractController
{
    #[Route('', name: 'app_generation_index', methods: ['GET'])]
    public function index(GenerationRepository $generationRepository): Response
    {
        $user = $this->getUser();

        if ($user) {
            $generations = $generationRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
        } else {
            $generations = [];
        }

        return $this->render('generation/index.html.twig', [
            'generations' => $generations,
        ]);
    }

    #[Route('/{id}', name: 'app_generation_show', methods: ['GET'])]
    public function show(Generation $generation): Response
    {
        if ($this->getUser() && $generation->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Vous n\'avez pas acces a cette generation.');
            return $this->redirectToRoute('app_generation_index');
        }

        return $this->render('generation/show.html.twig', [
            'generation' => $generation,
        ]);
    }
}
