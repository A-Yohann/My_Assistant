<?php

namespace App\Tests\Controller;

use App\Entity\Client;
use App\Entity\Devis;
use App\Entity\Entreprise;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ClientControllerTest extends WebTestCase
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
        $entreprise->setNomEntreprise('Entreprise Test Client');
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

    private function createTestClient($em, User $user, string $email = 'jean.dupont.client@example.com'): Client
    {
        $client = new Client();
        $client->setNom('Dupont');
        $client->setPrenom('Jean');
        $client->setEmail($email);
        $client->setTelephone('0612345678');
        $client->setCodePostal('75000');
        $client->setVille('Paris');
        $client->setPays('France');
        $client->setDateCreation(new \DateTime());
        $client->setUser($user);
        $em->persist($client);
        $em->flush();

        return $client;
    }

    private function createTestDevis($em, Entreprise $entreprise, Client $client, string $numero = 'DEV-TEST-CLIENT-0001'): Devis
    {
        $devis = new Devis();
        $devis->setNumeroDevis($numero);
        $devis->setDateEmission(new \DateTime());
        $devis->setDateValidite((new \DateTime())->modify('+30 days'));
        $devis->setMontantHT(1000.0);
        $devis->setTauxTVA(20.0);
        $devis->setMontantTtc(1200.0);
        $devis->setDescription('Devis de test');
        $devis->setDateCreation(new \DateTime());
        $devis->setEntreprise($entreprise);
        $devis->setClient($client);
        $devis->setEtat('en_attente');
        $em->persist($devis);
        $em->flush();

        return $devis;
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

    public function testClientIndexRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/clients');

        $this->assertResponseRedirects();
    }

    public function testClientIndexLoadsSuccessfullyWithClientHavingDevis(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.client.index@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);
        $testClient = $this->createTestClient($em, $user);
        $this->createTestDevis($em, $entreprise, $testClient);

        $client->loginUser($user);
        $client->request('GET', '/clients');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Dupont');

        $this->cleanUpAll($em, $email);
    }

    public function testClientShowDisplaysClientWithDevis(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.client.show@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);
        $testClient = $this->createTestClient($em, $user);
        $this->createTestDevis($em, $entreprise, $testClient);

        $client->loginUser($user);
        $client->request('GET', '/clients/' . $testClient->getIdClient());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Dupont');

        $this->cleanUpAll($em, $email);
    }

    public function testClientShowReturns404ForNonExistentClient(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.client.show404@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);

        $client->loginUser($user);
        $client->request('GET', '/clients/999999');

        $this->assertResponseStatusCodeSame(404);

        $this->cleanUpAll($em, $email);
    }

    public function testClientShowReturns404WhenClientBelongsToAnotherUser(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $emailOwner = 'test.client.owner@example.com';
        $emailIntruder = 'test.client.intruder@example.com';
        $this->cleanUpAll($em, $emailOwner);
        $this->cleanUpAll($em, $emailIntruder);

        $owner = $this->createTestUser($em, $passwordHasher, $emailOwner);
        $ownerClient = $this->createTestClient($em, $owner, 'client.du.owner@example.com');

        $intruder = $this->createTestUser($em, $passwordHasher, $emailIntruder);

        $client->loginUser($intruder);
        $client->request('GET', '/clients/' . $ownerClient->getIdClient());

        $this->assertResponseStatusCodeSame(404, 'Un utilisateur ne devrait pas pouvoir voir le client d\'un autre utilisateur.');

        $this->cleanUpAll($em, $emailOwner);
        $this->cleanUpAll($em, $emailIntruder);
    }

    public function testClientJsonReturnsClientDataAsJson(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.client.json@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $testClient = $this->createTestClient($em, $user);

        $client->loginUser($user);
        $client->request('GET', '/client/' . $testClient->getIdClient() . '/json');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Dupont', $data['nom']);
        $this->assertEquals('Jean', $data['prenom']);

        $this->cleanUpAll($em, $email);
    }

    public function testClientJsonReturns404ForNonExistentClient(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.client.json404@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);

        $client->loginUser($user);
        $client->request('GET', '/client/999999/json');

        $this->assertResponseStatusCodeSame(404);

        $this->cleanUpAll($em, $email);
    }
}