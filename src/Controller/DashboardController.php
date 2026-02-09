<?php

namespace App\Controller;

use App\Repository\GenerationRepository;
use App\Repository\PlanRepository;
use App\Repository\UserContactRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        GenerationRepository $generationRepository,
        UserContactRepository $contactRepository,
        PlanRepository $planRepository
    ): Response {
        $user = $this->getUser();

        if ($user) {
            $generations = $generationRepository->findBy(['user' => $user], ['createdAt' => 'DESC'], 5);
            $totalGenerations = $generationRepository->count(['user' => $user]);
            $totalContacts = $contactRepository->count(['user' => $user]);
        } else {
            $generations = [];
            $totalGenerations = 0;
            $totalContacts = 0;
        }

        $totalPlans = $planRepository->count(['active' => true]);

        return $this->render('dashboard/index.html.twig', [
            'generations' => $generations,
            'totalGenerations' => $totalGenerations,
            'totalContacts' => $totalContacts,
            'totalPlans' => $totalPlans,
        ]);
    }
}
