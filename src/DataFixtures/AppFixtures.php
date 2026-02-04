<?php

namespace App\DataFixtures;

use App\Entity\Plan;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $plansData = [
            ['name' => 'Free', 'description' => 'Plan gratuit pour découvrir le service. Limité à 5 générations par mois.', 'limitGeneration' => 5, 'role' => 'ROLE_USER', 'price' => '0.00'],
            ['name' => 'Basic', 'description' => 'Plan basique pour les particuliers. 50 générations par mois avec support email.', 'limitGeneration' => 50, 'role' => 'ROLE_BASIC', 'price' => '9.99'],
            ['name' => 'Premium', 'description' => 'Plan premium avec fonctionnalités avancées. 500 générations par mois, support prioritaire et accès aux nouvelles fonctionnalités.', 'limitGeneration' => 500, 'role' => 'ROLE_PREMIUM', 'price' => '29.99', 'specialPrice' => '24.99'],
        ];

        foreach ($plansData as $data) {
            $plan = new Plan();
            $plan->setName($data['name'])
                ->setDescription($data['description'])
                ->setLimitGeneration($data['limitGeneration'])
                ->setRole($data['role'])
                ->setPrice($data['price'])
                ->setActive(true);

            if (isset($data['specialPrice'])) {
                $plan->setSpecialPrice($data['specialPrice'])
                    ->setSpecialPriceFrom(new \DateTime())
                    ->setSpecialPriceTo(new \DateTime('+30 days'));
            }

            $manager->persist($plan);
        }

        $manager->flush();
    }
}
