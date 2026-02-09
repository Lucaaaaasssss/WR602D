<?php

namespace App\Controller;

use App\Repository\PlanRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/plan')]
class PlanController extends AbstractController
{
    #[Route('', name: 'app_plan_index', methods: ['GET'])]
    public function index(PlanRepository $planRepository): Response
    {
        $plans = $planRepository->findBy(['active' => true], ['price' => 'ASC']);

        return $this->render('plan/index.html.twig', [
            'plans' => $plans,
        ]);
    }
}
