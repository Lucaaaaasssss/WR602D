<?php

namespace App\Controller;

use App\Entity\Plan;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PaymentController extends AbstractController
{
    public function __construct(
        private string $stripeSecretKey,
        private string $stripeWebhookSecret,
    ) {}

    #[Route('/api/payment/checkout', name: 'api_payment_checkout', methods: ['POST'])]
    public function checkout(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié.'], 401);
        }

        $data   = json_decode($request->getContent(), true);
        $planId = $data['planId'] ?? null;

        $plan = $em->getRepository(Plan::class)->find($planId);
        if (!$plan || !$plan->getStripePriceId()) {
            return $this->json(['error' => 'Plan invalide ou non configuré dans Stripe.'], 400);
        }

        $stripe  = new StripeClient($this->stripeSecretKey);
        $session = $stripe->checkout->sessions->create([
            'mode'        => 'subscription',
            'line_items'  => [[
                'price'    => $plan->getStripePriceId(),
                'quantity' => 1,
            ]],
            'metadata' => [
                'user_id' => $user->getId(),
                'plan_id' => $plan->getId(),
            ],
            'customer_email'    => $user->getEmail(),
            'success_url'       => 'http://localhost:5173/payment/success',
            'cancel_url'        => 'http://localhost:5173/payment/cancel',
        ]);

        return $this->json(['url' => $session->url]);
    }

    #[Route('/payment/success', name: 'payment_success', methods: ['GET'])]
    public function success(): RedirectResponse
    {
        return new RedirectResponse('http://localhost:5173/payment/success');
    }

    #[Route('/payment/cancel', name: 'payment_cancel', methods: ['GET'])]
    public function cancel(): RedirectResponse
    {
        return new RedirectResponse('http://localhost:5173/payment/cancel');
    }

    #[Route('/payment/webhook', name: 'payment_webhook', methods: ['POST'])]
    public function webhook(Request $request, EntityManagerInterface $em): Response
    {
        $stripe  = new StripeClient($this->stripeSecretKey);
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $this->stripeWebhookSecret);
        } catch (\Exception $e) {
            return new Response('Webhook error: ' . $e->getMessage(), 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $userId  = $session->metadata->user_id ?? null;
            $planId  = $session->metadata->plan_id ?? null;

            if ($userId && $planId) {
                $user = $em->getRepository(User::class)->find($userId);
                $plan = $em->getRepository(Plan::class)->find($planId);

                if ($user && $plan) {
                    $user->setRoles([$plan->getRole()]);
                    $em->flush();
                }
            }
        }

        return new Response('OK');
    }
}
