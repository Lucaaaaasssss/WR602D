<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class PasswordResetController extends AbstractController
{
    #[Route('/api/forgot-password', name: 'api_forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        EntityManagerInterface $em,
    ): JsonResponse {
        $data  = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        // Toujours répondre OK pour ne pas révéler si l'email existe
        if (!$user) {
            return $this->json(['message' => 'Si cet email existe, un lien a été envoyé.']);
        }

        $token = bin2hex(random_bytes(32));
        $user->setResetToken($token);
        $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
        $em->flush();

        $resetUrl = 'http://localhost:5173/reset-password?token=' . $token;

        // Sans mailer, on retourne le lien directement
        return $this->json([
            'message'  => 'Lien généré.',
            'resetUrl' => $resetUrl,
        ]);
    }

    #[Route('/api/reset-password', name: 'api_reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
    ): JsonResponse {
        $data     = json_decode($request->getContent(), true);
        $token    = $data['token'] ?? '';
        $password = $data['password'] ?? '';

        if (strlen($password) < 8) {
            return $this->json(['error' => 'Le mot de passe doit faire au moins 8 caractères.'], 422);
        }

        $user = $em->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (!$user || !$user->getResetTokenExpiresAt() || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            return $this->json(['error' => 'Lien invalide ou expiré.'], 400);
        }

        $user->setPassword($hasher->hashPassword($user, $password));
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);
        $em->flush();

        return $this->json(['message' => 'Mot de passe mis à jour.']);
    }
}
