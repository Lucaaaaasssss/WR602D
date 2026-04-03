<?php

namespace App\Controller;

use App\Repository\PlanRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/plan')]
class PlanController extends AbstractController
{
    #[Route('', name: 'app_plan_index', methods: ['GET'])]
    public function index(): Response
    {
        return new RedirectResponse('http://localhost:5173/plans');
    }

    #[Route('/api', name: 'app_plan_api', methods: ['GET'])]
    public function apiIndex(PlanRepository $planRepository): JsonResponse
    {
        $plans = $planRepository->findBy(['active' => true], ['price' => 'ASC']);

        $data = array_map(fn($p) => [
            'id'               => $p->getId(),
            'name'             => $p->getName(),
            'description'      => $p->getDescription(),
            'price'            => $p->getPrice(),
            'specialPrice'     => $p->getSpecialPrice(),
            'specialPriceFrom' => $p->getSpecialPriceFrom()?->format('Y-m-d'),
            'specialPriceTo'   => $p->getSpecialPriceTo()?->format('Y-m-d'),
            'limitGeneration'  => $p->getLimitGeneration(),
            'role'             => $p->getRole(),
            'image'            => $p->getImage(),
        ], $plans);

        return $this->json($data);
    }
}
