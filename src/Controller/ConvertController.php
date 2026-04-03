<?php

namespace App\Controller;

use App\Entity\Generation;
use App\Repository\GenerationRepository;
use App\Repository\PlanRepository;
use App\Service\GotenbergService;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/convert')]
class ConvertController extends AbstractController
{
    public function __construct(
        private GotenbergService $gotenbergService,
        private EntityManagerInterface $entityManager,
        private GenerationRepository $generationRepository,
        private PlanRepository $planRepository,
        private MailerService $mailerService,
        private string $shareDir,
    ) {
    }

    private function checkQuota(): ?Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $plan = null;
        foreach ($user->getRoles() as $role) {
            $plan = $this->planRepository->findOneBy(['role' => $role, 'active' => true]);
            if ($plan) break;
        }

        $limit = $plan ? $plan->getLimitGeneration() : 5;
        if ($limit >= 999) return null;

        $used = $this->generationRepository->countToday($user);
        if ($used >= $limit) {
            return $this->json([
                'error' => "Quota journalier atteint : $used / $limit générations utilisées aujourd'hui. Passez à un plan supérieur.",
                'quota' => ['used' => $used, 'limit' => $limit],
            ], Response::HTTP_FORBIDDEN);
        }

        return null;
    }

    private function saveFile(string $content, string $filename): void
    {
        if (!is_dir($this->shareDir)) {
            mkdir($this->shareDir, 0755, true);
        }
        file_put_contents($this->shareDir . '/' . $filename, $content);
    }

    private function recordAndNotify(string $filename, string $type, string $content): Generation
    {
        $generation = new Generation();
        $generation->setFile($filename);
        $generation->setType($type);
        $generation->setUser($this->getUser());
        $this->entityManager->persist($generation);
        $this->entityManager->flush();

        try {
            $this->mailerService->sendPdfGenerated($this->getUser(), $filename, $content, $type);
        } catch (\Exception) {
            // Ne pas bloquer la réponse si l'email échoue
        }

        return $generation;
    }

    #[Route('/url', name: 'app_convert_url', methods: ['POST'])]
    public function url(Request $request): Response
    {
        if ($err = $this->checkQuota()) return $err;

        $url = $request->request->get('url') ?? json_decode($request->getContent(), true)['url'] ?? null;
        if (!$url) {
            return $this->json(['error' => 'Le paramètre "url" est requis'], 400);
        }

        try {
            $content  = $this->gotenbergService->convertUrlToPdf($url);
            $filename = 'url_' . date('Ymd_His') . '.pdf';
            $this->saveFile($content, $filename);
            $this->recordAndNotify($filename, 'url', $content);

            return new Response($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/html', name: 'app_convert_html', methods: ['POST'])]
    public function html(Request $request): Response
    {
        if ($err = $this->checkQuota()) return $err;

        $html = $request->request->get('html') ?? json_decode($request->getContent(), true)['html'] ?? null;
        if (!$html) {
            return $this->json(['error' => 'Le paramètre "html" est requis'], 400);
        }

        try {
            $content  = $this->gotenbergService->convertHtmlToPdf($html);
            $filename = 'html_' . date('Ymd_His') . '.pdf';
            $this->saveFile($content, $filename);
            $this->recordAndNotify($filename, 'html', $content);

            return new Response($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/markdown', name: 'app_convert_markdown', methods: ['POST'])]
    public function markdown(Request $request): Response
    {
        if ($err = $this->checkQuota()) return $err;

        $markdown = $request->request->get('markdown') ?? json_decode($request->getContent(), true)['markdown'] ?? null;
        if (!$markdown) {
            return $this->json(['error' => 'Le paramètre "markdown" est requis'], 400);
        }

        try {
            $content  = $this->gotenbergService->convertMarkdownToPdf($markdown);
            $filename = 'markdown_' . date('Ymd_His') . '.pdf';
            $this->saveFile($content, $filename);
            $this->recordAndNotify($filename, 'markdown', $content);

            return new Response($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/office', name: 'app_convert_office', methods: ['POST'])]
    public function office(Request $request): Response
    {
        if ($err = $this->checkQuota()) return $err;

        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'Un fichier est requis'], 400);
        }

        try {
            $content  = $this->gotenbergService->convertOfficeToPdf(
                file_get_contents($file->getPathname()),
                $file->getClientOriginalName()
            );
            $filename = 'office_' . date('Ymd_His') . '.pdf';
            $this->saveFile($content, $filename);
            $this->recordAndNotify($filename, 'office', $content);

            return new Response($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/merge', name: 'app_convert_merge', methods: ['POST'])]
    public function merge(Request $request): Response
    {
        if ($err = $this->checkQuota()) return $err;

        $files = $request->files->get('files');
        if (!$files || count($files) < 2) {
            return $this->json(['error' => 'Au moins 2 fichiers PDF sont requis'], 400);
        }

        try {
            $pdfContents = array_map(fn($f) => file_get_contents($f->getPathname()), $files);
            $content     = $this->gotenbergService->mergePdfs($pdfContents);
            $filename    = 'merge_' . date('Ymd_His') . '.pdf';
            $this->saveFile($content, $filename);
            $this->recordAndNotify($filename, 'merge', $content);

            return new Response($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/screenshot', name: 'app_convert_screenshot', methods: ['POST'])]
    public function screenshot(Request $request): Response
    {
        if ($err = $this->checkQuota()) return $err;

        $url = $request->request->get('url') ?? json_decode($request->getContent(), true)['url'] ?? null;
        if (!$url) {
            return $this->json(['error' => 'Le paramètre "url" est requis'], 400);
        }

        try {
            $content  = $this->gotenbergService->screenshotUrl($url);
            $filename = 'screenshot_' . date('Ymd_His') . '.png';
            $this->saveFile($content, $filename);
            $this->recordAndNotify($filename, 'screenshot', $content);

            return new Response($content, 200, [
                'Content-Type' => 'image/png',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/wysiwyg', name: 'app_convert_wysiwyg', methods: ['POST'])]
    public function wysiwyg(Request $request): Response
    {
        if ($err = $this->checkQuota()) return $err;

        $html = $request->request->get('html') ?? json_decode($request->getContent(), true)['html'] ?? null;
        if (!$html) {
            return $this->json(['error' => 'Le paramètre "html" est requis'], 400);
        }

        $fullHtml = <<<HTMLDOC
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 40px; line-height: 1.6; color: #1e293b; }
        img { max-width: 100%; }
    </style>
</head>
<body>$html</body>
</html>
HTMLDOC;

        try {
            $content  = $this->gotenbergService->convertHtmlToPdf($fullHtml);
            $filename = 'wysiwyg_' . date('Ymd_His') . '.pdf';
            $this->saveFile($content, $filename);
            $this->recordAndNotify($filename, 'wysiwyg', $content);

            return new Response($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/quota', name: 'app_convert_quota', methods: ['GET'])]
    public function quota(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $plan = null;
        foreach ($user->getRoles() as $role) {
            $plan = $this->planRepository->findOneBy(['role' => $role, 'active' => true]);
            if ($plan) break;
        }

        $limit = $plan ? $plan->getLimitGeneration() : 5;
        $used  = $this->generationRepository->countToday($user);

        return $this->json([
            'used'      => (int) $used,
            'limit'     => $limit,
            'remaining' => max(0, $limit - $used),
            'unlimited' => $limit >= 999,
        ]);
    }

    #[Route('/download/{filename}', name: 'app_convert_download', methods: ['GET'])]
    public function download(string $filename): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $generation = $this->generationRepository->findOneBy(['file' => $filename, 'user' => $user]);
        if (!$generation) {
            return $this->json(['error' => 'Fichier non trouvé'], 404);
        }

        $path = $this->shareDir . '/' . $filename;
        if (!file_exists($path)) {
            return $this->json(['error' => 'Fichier introuvable sur le serveur'], 404);
        }

        $ext         = pathinfo($filename, PATHINFO_EXTENSION);
        $contentType = $ext === 'png' ? 'image/png' : 'application/pdf';

        return new Response(file_get_contents($path), 200, [
            'Content-Type'        => $contentType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    #[Route('/share/{id}', name: 'app_convert_share', methods: ['POST'])]
    public function share(int $id, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $generation = $this->generationRepository->find($id);
        if (!$generation || $generation->getUser() !== $user) {
            return $this->json(['error' => 'Génération non trouvée'], 404);
        }

        $data       = json_decode($request->getContent(), true);
        $contactIds = $data['contactIds'] ?? [];

        if (empty($contactIds)) {
            return $this->json(['error' => 'Aucun contact sélectionné'], 400);
        }

        $path = $this->shareDir . '/' . $generation->getFile();
        if (!file_exists($path)) {
            return $this->json(['error' => 'Fichier introuvable sur le serveur'], 404);
        }

        $fileContent  = file_get_contents($path);
        $contactRepo  = $this->entityManager->getRepository(\App\Entity\UserContact::class);
        $sent         = 0;

        foreach ($contactIds as $contactId) {
            $contact = $contactRepo->find($contactId);
            if (!$contact || $contact->getUser() !== $user) continue;

            $generation->addUserContact($contact);

            try {
                $this->mailerService->sendPdfShared($contact->getEmail(), $user, $generation->getFile(), $fileContent);
                $sent++;
            } catch (\Exception) {
                // Continuer même si un email échoue
            }
        }

        $this->entityManager->flush();

        return $this->json(['message' => "$sent email(s) envoyé(s) avec succès"]);
    }
}
