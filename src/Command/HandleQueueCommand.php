<?php

namespace App\Command;

use App\Entity\Generation;
use App\Entity\MergeQueue;
use App\Repository\MergeQueueRepository;
use App\Service\GotenbergService;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:handle-queue',
    description: 'Traite la file d\'attente de fusion de PDFs',
)]
class HandleQueueCommand extends Command
{
    public function __construct(
        private MergeQueueRepository $mergeQueueRepository,
        private EntityManagerInterface $entityManager,
        private GotenbergService $gotenbergService,
        private MailerService $mailerService,
        private string $shareDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Nombre max d\'éléments à traiter', 10);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io    = new SymfonyStyle($input, $output);
        $limit = (int) $input->getOption('limit');

        $items = $this->mergeQueueRepository->findPending($limit);

        if (empty($items)) {
            $io->info('Aucun élément en attente dans la file.');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Traitement de %d élément(s) en attente...', count($items)));

        foreach ($items as $item) {
            $this->processItem($item, $io);
        }

        $io->success('File d\'attente traitée.');
        return Command::SUCCESS;
    }

    private function processItem(MergeQueue $item, SymfonyStyle $io): void
    {
        $item->setStatus(MergeQueue::STATUS_PROCESSING);
        $this->entityManager->flush();

        $io->text(sprintf('Traitement de l\'élément #%d (utilisateur: %s)', $item->getId(), $item->getUser()->getEmail()));

        try {
            $filenames   = $item->getFilesArray();
            $pdfContents = [];

            foreach ($filenames as $filename) {
                $path = $this->shareDir . '/' . trim($filename);
                if (!file_exists($path)) {
                    throw new \RuntimeException("Fichier introuvable : $filename");
                }
                $pdfContents[] = file_get_contents($path);
            }

            if (count($pdfContents) < 2) {
                throw new \RuntimeException('Au moins 2 fichiers PDF sont nécessaires pour la fusion');
            }

            $merged   = $this->gotenbergService->mergePdfs($pdfContents);
            $filename = 'queue_merge_' . date('Ymd_His') . '_' . $item->getId() . '.pdf';

            if (!is_dir($this->shareDir)) {
                mkdir($this->shareDir, 0755, true);
            }
            file_put_contents($this->shareDir . '/' . $filename, $merged);

            $generation = new Generation();
            $generation->setFile($filename);
            $generation->setType('merge');
            $generation->setUser($item->getUser());
            $this->entityManager->persist($generation);

            $item->setStatus(MergeQueue::STATUS_DONE);
            $item->setOutputFile($filename);
            $item->setProcessedAt(new \DateTime());
            $this->entityManager->flush();

            try {
                $this->mailerService->sendPdfGenerated($item->getUser(), $filename, $merged, 'merge');
            } catch (\Exception) {
                // Ne pas bloquer si l'email échoue
            }

            $io->text("  -> Succès : $filename");
        } catch (\Exception $e) {
            $item->setStatus(MergeQueue::STATUS_FAILED);
            $item->setProcessedAt(new \DateTime());
            $this->entityManager->flush();

            $io->error(sprintf('  -> Échec : %s', $e->getMessage()));
        }
    }
}
