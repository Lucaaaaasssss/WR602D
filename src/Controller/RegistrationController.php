<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET'])]
    public function index(): Response
    {
        if ($this->getUser()) {
            return new RedirectResponse('http://localhost:5173/dashboard');
        }

        return new RedirectResponse('http://localhost:5173/register');
    }

    #[Route('/register', name: 'app_register_post', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        Security $security,
    ): Response {
        $email           = $request->request->get('email');
        $firstname       = $request->request->get('firstname');
        $lastname        = $request->request->get('lastname');
        $plainPassword   = $request->request->get('password');
        $confirmPassword = $request->request->get('confirm_password');

        if ($plainPassword !== $confirmPassword) {
            return new RedirectResponse('http://localhost:5173/register?error=password_mismatch');
        }

        $existing = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            return new RedirectResponse('http://localhost:5173/register?error=email_exists');
        }

        $user = new User();
        $user->setEmail($email);
        $user->setFirstname($firstname);
        $user->setLastname($lastname);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

        $entityManager->persist($user);
        $entityManager->flush();

        $security->login($user, 'form_login', 'main');

        return new RedirectResponse('http://localhost:5173/dashboard');
    }
}
