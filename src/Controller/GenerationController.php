<?php

namespace App\Controller;

use App\Repository\GenerationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/generation')]
class GenerationController extends AbstractController
{
    #[Route('', name: 'app_generation_index', methods: ['GET'])]
    public function index(): Response
    {
        return new RedirectResponse('http://localhost:5173/history');
    }

    #[Route('/api', name: 'app_generation_api', methods: ['GET'])]
    public function apiIndex(GenerationRepository $generationRepository): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $generations = $generationRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->json(array_map(fn($g) => [
            'id'        => $g->getId(),
            'file'      => $g->getFile(),
            'type'      => $g->getType(),
            'createdAt' => $g->getCreatedAt()?->format('Y-m-d H:i:s'),
        ], $generations));
    }
}
