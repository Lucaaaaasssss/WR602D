<?php

namespace App\DataFixtures;

use App\Entity\Generation;
use App\Entity\Plan;
use App\Entity\User;
use App\Entity\UserContact;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Plans
        $plans = [];
        $plansData = [
            ['name' => 'Free', 'description' => 'Plan gratuit pour découvrir le service', 'limitGeneration' => 5, 'role' => 'ROLE_USER', 'price' => '0.00'],
            ['name' => 'Basic', 'description' => 'Plan basique pour les particuliers', 'limitGeneration' => 50, 'role' => 'ROLE_BASIC', 'price' => '9.99'],
            ['name' => 'Pro', 'description' => 'Plan professionnel avec fonctionnalités avancées', 'limitGeneration' => 200, 'role' => 'ROLE_PRO', 'price' => '29.99', 'specialPrice' => '19.99'],
            ['name' => 'Enterprise', 'description' => 'Plan entreprise illimité', 'limitGeneration' => 1000, 'role' => 'ROLE_ENTERPRISE', 'price' => '99.99'],
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
            $plans[] = $plan;
        }

        // Users
        $users = [];
        $usersData = [
            ['email' => 'admin@example.com', 'firstname' => 'Admin', 'lastname' => 'User', 'phone' => '0612345678', 'color' => '#FF5733'],
            ['email' => 'john@example.com', 'firstname' => 'John', 'lastname' => 'Doe', 'phone' => '0698765432', 'color' => '#33FF57'],
            ['email' => 'jane@example.com', 'firstname' => 'Jane', 'lastname' => 'Smith', 'phone' => '0611223344', 'color' => '#3357FF'],
            ['email' => 'lucas@example.com', 'firstname' => 'Lucas', 'lastname' => 'Martin', 'phone' => '0655443322', 'color' => '#F333FF'],
            ['email' => 'marie@example.com', 'firstname' => 'Marie', 'lastname' => 'Dupont', 'phone' => '0677889900', 'color' => '#33FFF3'],
        ];

        foreach ($usersData as $data) {
            $user = new User();
            $user->setEmail($data['email'])
                ->setPassword(password_hash('password123', PASSWORD_BCRYPT))
                ->setFirstname($data['firstname'])
                ->setLastname($data['lastname'])
                ->setPhone($data['phone'])
                ->setFavoriteColor($data['color'])
                ->setDob(new \DateTime('-' . rand(20, 50) . ' years'));

            $manager->persist($user);
            $users[] = $user;
        }

        // UserContacts (3 contacts par utilisateur)
        $contacts = [];
        $contactNames = [
            ['firstname' => 'Pierre', 'lastname' => 'Bernard'],
            ['firstname' => 'Sophie', 'lastname' => 'Leroy'],
            ['firstname' => 'Thomas', 'lastname' => 'Moreau'],
            ['firstname' => 'Emma', 'lastname' => 'Laurent'],
            ['firstname' => 'Hugo', 'lastname' => 'Garcia'],
            ['firstname' => 'Léa', 'lastname' => 'Roux'],
            ['firstname' => 'Louis', 'lastname' => 'Fournier'],
            ['firstname' => 'Chloé', 'lastname' => 'Morel'],
            ['firstname' => 'Nathan', 'lastname' => 'Girard'],
            ['firstname' => 'Manon', 'lastname' => 'Andre'],
        ];

        $contactIndex = 0;
        foreach ($users as $user) {
            for ($i = 0; $i < 3; $i++) {
                $name = $contactNames[$contactIndex % count($contactNames)];
                $contact = new UserContact();
                $contact->setUser($user)
                    ->setFirstname($name['firstname'])
                    ->setLastname($name['lastname'])
                    ->setEmail(strtolower($name['firstname'] . '.' . $name['lastname'] . '@example.com'));

                $manager->persist($contact);
                $contacts[] = $contact;
                $contactIndex++;
            }
        }

        // Generations (2 par utilisateur avec des contacts associés)
        foreach ($users as $index => $user) {
            for ($i = 0; $i < 2; $i++) {
                $generation = new Generation();
                $generation->setUser($user)
                    ->setFile('generation_' . ($index * 2 + $i + 1) . '.pdf');

                // Ajouter 1 à 3 contacts à cette génération
                $userContacts = array_filter($contacts, fn($c) => $c->getUser() === $user);
                $userContacts = array_values($userContacts);
                $nbContacts = rand(1, min(3, count($userContacts)));

                for ($j = 0; $j < $nbContacts; $j++) {
                    $generation->addUserContact($userContacts[$j]);
                }

                $manager->persist($generation);
            }
        }

        $manager->flush();
    }
}
