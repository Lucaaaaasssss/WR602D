<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class MailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $from,
    ) {
    }

    public function sendResetPasswordLink(User $user, string $resetUrl): void
    {
        $email = (new Email())
            ->from($this->from)
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->html(<<<HTML
                <h2>Réinitialisation de mot de passe</h2>
                <p>Bonjour,</p>
                <p>Cliquez sur le lien ci-dessous pour réinitialiser votre mot de passe (valable 1 heure) :</p>
                <p><a href="{$resetUrl}" style="background:#2563EB;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;">Réinitialiser mon mot de passe</a></p>
                <p>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
            HTML);

        $this->mailer->send($email);
    }

    public function sendPdfGenerated(User $user, string $filename, string $fileContent, string $type): void
    {
        $ext         = pathinfo($filename, PATHINFO_EXTENSION);
        $contentType = $ext === 'png' ? 'image/png' : 'application/pdf';
        $typeLabel   = match ($type) {
            'url'        => 'URL → PDF',
            'html'       => 'HTML → PDF',
            'markdown'   => 'Markdown → PDF',
            'office'     => 'Office → PDF',
            'merge'      => 'Fusion PDF',
            'screenshot' => 'Capture d\'écran',
            'wysiwyg'    => 'Éditeur WYSIWYG → PDF',
            default      => 'Génération PDF',
        };

        $email = (new Email())
            ->from($this->from)
            ->to($user->getEmail())
            ->subject("Votre fichier est prêt : $typeLabel")
            ->html(<<<HTML
                <h2>Votre fichier a été généré</h2>
                <p>Bonjour,</p>
                <p>Votre fichier généré via <strong>$typeLabel</strong> est disponible en pièce jointe.</p>
                <p>Nom du fichier : <code>$filename</code></p>
                <p>Merci d'utiliser PDFGen !</p>
            HTML)
            ->attach($fileContent, $filename, $contentType);

        $this->mailer->send($email);
    }

    public function sendPdfShared(string $toEmail, User $from, string $filename, string $fileContent): void
    {
        $ext         = pathinfo($filename, PATHINFO_EXTENSION);
        $contentType = $ext === 'png' ? 'image/png' : 'application/pdf';
        $fromName    = $from->getFirstname() . ' ' . $from->getLastname();

        $email = (new Email())
            ->from($this->from)
            ->to($toEmail)
            ->subject("$fromName vous a partagé un fichier")
            ->html(<<<HTML
                <h2>Un fichier vous a été partagé</h2>
                <p><strong>$fromName</strong> vous a envoyé le fichier <code>$filename</code> via PDFGen.</p>
                <p>Vous le trouverez en pièce jointe.</p>
            HTML)
            ->attach($fileContent, $filename, $contentType);

        $this->mailer->send($email);
    }
}
