<?php

namespace App\Controller;

use App\Entity\Generation;
use App\Repository\GenerationRepository;
use App\Repository\PlanRepository;
use App\Service\GotenbergService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pdf')]
class PdfController extends AbstractController
{
    public function __construct(
        private GotenbergService $gotenbergService,
        private EntityManagerInterface $entityManager,
        private GenerationRepository $generationRepository,
        private PlanRepository $planRepository,
    ) {
    }

    private function checkLimit(): ?Response
    {
        $user = $this->getUser();
        if (!$user) return null;

        $plan = null;
        foreach ($user->getRoles() as $role) {
            $plan = $this->planRepository->findOneBy(['role' => $role, 'active' => true]);
            if ($plan) break;
        }

        $limit = $plan ? $plan->getLimitGeneration() : 5;
        if ($limit >= 999) return null; // illimité

        $used = $this->generationRepository->countThisMonth($user);
        if ($used >= $limit) {
            return new Response(
                json_encode(['error' => "Limite atteinte : votre plan autorise $limit générations par mois. Passez à un plan supérieur."]),
                Response::HTTP_FORBIDDEN,
                ['Content-Type' => 'application/json']
            );
        }

        return null;
    }

    #[Route('', name: 'app_pdf_page', methods: ['GET'])]
    public function index(): Response
    {
        return new \Symfony\Component\HttpFoundation\RedirectResponse('http://localhost:5173/history');
    }

    #[Route('/generate', name: 'app_pdf_generate', methods: ['GET', 'POST'])]
    public function generate(Request $request): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('error', 'Veuillez creer un compte ou vous connecter pour generer un PDF.');
            return $this->redirectToRoute('app_pdf_page');
        }

        $defaultHtml = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Document PDF</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; color: #333; }
        h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .content { margin-top: 20px; line-height: 1.6; }
        .footer { margin-top: 40px; font-size: 12px; color: #7f8c8d; text-align: center; }
    </style>
</head>
<body>
    <h1>Document généré par Gotenberg</h1>
    <div class="content">
        <p>Ce document PDF a été généré automatiquement via l'API Gotenberg.</p>
        <p>Date de génération : %s</p>
    </div>
    <div class="footer"><p>Généré avec Symfony HttpClient & Gotenberg</p></div>
</body>
</html>
HTML;

        if ($limit = $this->checkLimit()) return $limit;

        $htmlContent = $request->request->get('html', sprintf($defaultHtml, date('d/m/Y H:i:s')));

        try {
            $pdfContent = $this->gotenbergService->convertHtmlToPdf($htmlContent);

            $filename = 'document_' . date('Ymd_His') . '.pdf';

            $generation = new Generation();
            $generation->setFile($filename);
            $generation->setUser($this->getUser());
            $this->entityManager->persist($generation);
            $this->entityManager->flush();

            return new Response($pdfContent, Response::HTTP_OK, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            return new Response(
                'Erreur lors de la génération du PDF : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/from-url', name: 'app_pdf_from_url', methods: ['GET', 'POST'])]
    public function fromUrl(Request $request): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('error', 'Veuillez creer un compte ou vous connecter pour convertir une URL en PDF.');
            return $this->redirectToRoute('app_pdf_page');
        }

        if ($limit = $this->checkLimit()) return $limit;

        $url = $request->query->get('url') ?? $request->request->get('url');

        if (!$url) {
            return new Response(
                'Le paramètre "url" est requis',
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $pdfContent = $this->gotenbergService->convertUrlToPdf($url);

            $filename = 'url_' . date('Ymd_His') . '.pdf';

            $generation = new Generation();
            $generation->setFile($filename);
            $generation->setUser($this->getUser());
            $this->entityManager->persist($generation);
            $this->entityManager->flush();

            return new Response($pdfContent, Response::HTTP_OK, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            return new Response(
                'Erreur lors de la génération du PDF : ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
