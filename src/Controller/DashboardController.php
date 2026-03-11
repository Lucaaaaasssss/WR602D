<?php

namespace App\Controller;

use App\Repository\GenerationRepository;
use App\Repository\PlanRepository;
use App\Repository\UserContactRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(): Response
    {
        return new RedirectResponse('http://localhost:5173/dashboard');
    }

    #[Route('/api/dashboard', name: 'app_dashboard_api', methods: ['GET'])]
    public function apiIndex(
        GenerationRepository $generationRepository,
        UserContactRepository $contactRepository,
        PlanRepository $planRepository
    ): JsonResponse {
        $user = $this->getUser();

        if ($user) {
            $recentGenerations = $generationRepository->findBy(['user' => $user], ['createdAt' => 'DESC'], 5);
            $totalGenerations  = $generationRepository->count(['user' => $user]);
            $totalContacts     = $contactRepository->count(['user' => $user]);
        } else {
            $recentGenerations = [];
            $totalGenerations  = 0;
            $totalContacts     = 0;
        }

        $totalPlans = $planRepository->count(['active' => true]);

        $currentPlan = null;
        if ($user) {
            $roles = $user->getRoles();
            foreach ($roles as $role) {
                $plan = $planRepository->findOneBy(['role' => $role, 'active' => true]);
                if ($plan) { $currentPlan = ['id' => $plan->getId(), 'name' => $plan->getName(), 'price' => $plan->getPrice()]; break; }
            }
        }

        return $this->json([
            'user' => $user ? [
                'email'     => $user->getEmail(),
                'firstname' => $user->getFirstname(),
                'lastname'  => $user->getLastname(),
                'plan'      => $currentPlan,
            ] : null,
            'totalGenerations'  => $totalGenerations,
            'totalContacts'     => $totalContacts,
            'totalPlans'        => $totalPlans,
            'recentGenerations' => array_map(fn($g) => [
                'id'        => $g->getId(),
                'file'      => $g->getFile(),
                'createdAt' => $g->getCreatedAt()?->format('Y-m-d H:i:s'),
            ], $recentGenerations),
        ]);
    }
}
