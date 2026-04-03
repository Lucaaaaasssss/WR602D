<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(): Response
    {
        if ($this->getUser()) {
            return new RedirectResponse('http://localhost:5173/dashboard');
        }

        return new RedirectResponse('http://localhost:5173/login');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void {}
}
