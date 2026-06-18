<?php

namespace App\Tests\Controller;

use App\Entity\Entreprise;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class EntrepriseControllerTest extends WebTestCase
{
    private function createTestUser($em, $passwordHasher, string $email): User
    {
        $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            $em->remove($existing);
            $em->flush();
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, 'TestPassword123!'));
        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function createTestEntreprise($em, User $user, string $nom = 'Entreprise Test'): Entreprise
    {
        $entreprise = new Entreprise();
        $entreprise->setNomEntreprise($nom);
        $entreprise->setSiret('12345678900012');
        $entreprise->setEmail('contact.entreprise@example.com');
        $entreprise->setFormeJuridique(true);
        $entreprise->setDateCreation(new \DateTime());
        $entreprise->setLogo('logo.png');
        $entreprise->setRoles(true);
        $entreprise->setNumeroRue(10);
        $entreprise->setNomRue('Rue de Test');
        $entreprise->setComplementAdresse('');
        $entreprise->setCodePostal('75000');
        $entreprise->setVille('Paris');
        $entreprise->setPays('France');
        $entreprise->setType(true);
        $entreprise->setUser($user);
        $em->persist($entreprise);
        $em->flush();

        return $entreprise;
    }

    private function cleanUpAll($em, string $email): void
    {
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return;
        }

        $entreprises = $em->getRepository(Entreprise::class)->findBy(['user' => $user]);
        foreach ($entreprises as $entreprise) {
            $em->remove($entreprise);
        }

        $em->remove($user);
        $em->flush();
    }

    public function testSwitcherRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/entreprise/switcher/1');

        $this->assertResponseRedirects();
    }

    public function testSwitcherActivatesOwnedEntreprise(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.entreprise.switcher@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entrepriseA = $this->createTestEntreprise($em, $user, 'Entreprise A');
        $entrepriseB = $this->createTestEntreprise($em, $user, 'Entreprise B');

        $client->loginUser($user);
        $client->request('GET', '/entreprise/switcher/' . $entrepriseA->getId());
        $this->assertResponseRedirects();

        $client->request('GET', '/entreprise/switcher/' . $entrepriseB->getId());
        $this->assertResponseRedirects();

        $session = $client->getRequest()->getSession();
        $this->assertEquals($entrepriseB->getId(), $session->get('entreprise_active_id'));

        $this->cleanUpAll($em, $email);
    }

    public function testSwitcherDoesNotActivateEntrepriseOfAnotherUser(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $emailOwner = 'test.entreprise.owner@example.com';
        $emailIntruder = 'test.entreprise.intruder@example.com';
        $this->cleanUpAll($em, $emailOwner);
        $this->cleanUpAll($em, $emailIntruder);

        $owner = $this->createTestUser($em, $passwordHasher, $emailOwner);
        $ownerEntreprise = $this->createTestEntreprise($em, $owner, 'Entreprise du Owner');

        $intruder = $this->createTestUser($em, $passwordHasher, $emailIntruder);
        $intruderEntreprise = $this->createTestEntreprise($em, $intruder, 'Entreprise Intruder');

        $client->loginUser($intruder);
        $client->request('GET', '/entreprise/switcher/' . $ownerEntreprise->getId());

        $this->assertResponseRedirects();

        $session = $client->getRequest()->getSession();
        $this->assertNotEquals($ownerEntreprise->getId(), $session->get('entreprise_active_id'));

        $this->cleanUpAll($em, $emailOwner);
        $this->cleanUpAll($em, $emailIntruder);
    }
}