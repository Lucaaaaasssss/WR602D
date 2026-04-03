<?php

namespace App\Controller;

use App\Entity\MergeQueue;
use App\Repository\MergeQueueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/queue')]
class QueueController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MergeQueueRepository $mergeQueueRepository,
        private string $shareDir,
    ) {
    }

    #[Route('/merge', name: 'app_queue_merge', methods: ['POST'])]
    public function addMerge(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $files = $request->files->get('files');
        if (!$files || count($files) < 2) {
            return $this->json(['error' => 'Au moins 2 fichiers PDF sont requis'], 400);
        }

        // Sauvegarder les fichiers uploadés dans le dossier share
        if (!is_dir($this->shareDir)) {
            mkdir($this->shareDir, 0755, true);
        }

        $savedFiles = [];
        foreach ($files as $file) {
            $filename     = 'queue_' . date('Ymd_His') . '_' . uniqid() . '.pdf';
            file_put_contents($this->shareDir . '/' . $filename, file_get_contents($file->getPathname()));
            $savedFiles[] = $filename;
        }

        $item = new MergeQueue();
        $item->setUser($user);
        $item->setFiles(implode(',', $savedFiles));
        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Demande de fusion ajoutée à la file d\'attente.',
            'queueId' => $item->getId(),
        ], 201);
    }

    #[Route('/status/{id}', name: 'app_queue_status', methods: ['GET'])]
    public function status(int $id): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $item = $this->mergeQueueRepository->find($id);
        if (!$item || $item->getUser() !== $user) {
            return $this->json(['error' => 'Élément non trouvé'], 404);
        }

        return $this->json([
            'id'          => $item->getId(),
            'status'      => $item->getStatus(),
            'outputFile'  => $item->getOutputFile(),
            'createdAt'   => $item->getCreatedAt()->format('Y-m-d H:i:s'),
            'processedAt' => $item->getProcessedAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/list', name: 'app_queue_list', methods: ['GET'])]
    public function list(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $items = $this->mergeQueueRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->json(array_map(fn($item) => [
            'id'          => $item->getId(),
            'status'      => $item->getStatus(),
            'outputFile'  => $item->getOutputFile(),
            'createdAt'   => $item->getCreatedAt()->format('Y-m-d H:i:s'),
            'processedAt' => $item->getProcessedAt()?->format('Y-m-d H:i:s'),
        ], $items));
    }
}
