<?php

namespace App\Tests\Controller;

use App\Entity\Client;
use App\Entity\Devis;
use App\Entity\Entreprise;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DevisControllerTest extends WebTestCase
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

    private function createTestEntreprise($em, User $user): Entreprise
    {
        $entreprise = new Entreprise();
        $entreprise->setNomEntreprise('Entreprise Test');
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
            $devisList = $em->getRepository(Devis::class)->findBy(['entreprise' => $entreprise]);
            foreach ($devisList as $devis) {
                $em->remove($devis);
            }
            $em->remove($entreprise);
        }

        $clients = $em->getRepository(Client::class)->findBy(['user' => $user]);
        foreach ($clients as $client) {
            $em->remove($client);
        }

        $em->remove($user);
        $em->flush();
    }

    public function testDevisIndexRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/devis');

        $this->assertResponseRedirects();
    }

    public function testDevisIndexLoadsSuccessfullyWhenAuthenticated(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.devis.index@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $this->createTestEntreprise($em, $user);

        $client->loginUser($user);
        $client->request('GET', '/devis');

        $this->assertResponseIsSuccessful();

        $this->cleanUpAll($em, $email);
    }

    public function testDevisIndexWithoutEntrepriseStillLoads(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.devis.noentreprise@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);

        $client->loginUser($user);
        $client->request('GET', '/devis');

        $this->assertResponseIsSuccessful();

        $this->cleanUpAll($em, $email);
    }
}