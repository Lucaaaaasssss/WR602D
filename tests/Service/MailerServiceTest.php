<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\MailerService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerServiceTest extends TestCase
{
    private function makeUser(string $email = 'test@example.com'): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstname('John');
        $user->setLastname('Doe');
        return $user;
    }

    public function testSendResetPasswordLinkSendsEmail(): void
    {
        $mailer  = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send')->with($this->isInstanceOf(Email::class));

        $service = new MailerService($mailer, 'noreply@test.com');
        $service->sendResetPasswordLink($this->makeUser(), 'http://localhost/reset?token=abc');
    }

    public function testSendPdfGeneratedSendsEmailWithAttachment(): void
    {
        $mailer  = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send')->with($this->isInstanceOf(Email::class));

        $service = new MailerService($mailer, 'noreply@test.com');
        $service->sendPdfGenerated($this->makeUser(), 'doc.pdf', '%PDF fake content', 'html');
    }

    public function testSendPdfSharedSendsEmail(): void
    {
        $mailer  = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send')->with($this->isInstanceOf(Email::class));

        $service = new MailerService($mailer, 'noreply@test.com');
        $service->sendPdfShared('contact@example.com', $this->makeUser(), 'doc.pdf', '%PDF fake content');
    }

    public function testAllConversionTypeLabels(): void
    {
        // Vérifie que la méthode ne lève pas d'exception pour chaque type
        $mailer  = $this->createMock(MailerInterface::class);
        $mailer->expects($this->exactly(7))->method('send');

        $service = new MailerService($mailer, 'noreply@test.com');
        $user    = $this->makeUser();
        $types   = ['url', 'html', 'markdown', 'office', 'merge', 'screenshot', 'wysiwyg'];

        foreach ($types as $type) {
            $service->sendPdfGenerated($user, "file.$type.pdf", '%PDF fake', $type);
        }
    }
}
