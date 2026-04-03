<?php

namespace App\Entity;

use App\Repository\MergeQueueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MergeQueueRepository::class)]
class MergeQueue
{
    public const STATUS_PENDING    = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DONE       = 'done';
    public const STATUS_FAILED     = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /** Noms de fichiers PDF à fusionner, séparés par une virgule */
    #[ORM\Column(type: Types::TEXT)]
    private ?string $files = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $outputFile = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $processedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getFiles(): ?string { return $this->files; }
    public function setFiles(string $files): static { $this->files = $files; return $this; }
    public function getFilesArray(): array { return array_filter(explode(',', $this->files ?? '')); }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getOutputFile(): ?string { return $this->outputFile; }
    public function setOutputFile(?string $outputFile): static { $this->outputFile = $outputFile; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }

    public function getProcessedAt(): ?\DateTimeInterface { return $this->processedAt; }
    public function setProcessedAt(?\DateTimeInterface $processedAt): static { $this->processedAt = $processedAt; return $this; }
}
