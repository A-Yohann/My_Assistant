<?php

namespace App\Tests\Controller;

use App\Entity\BonDeCommande;
use App\Entity\Client;
use App\Entity\Devis;
use App\Entity\Entreprise;
use App\Entity\Facture;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class BonDeCommandeControllerTest extends WebTestCase
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
        $entreprise->setNomEntreprise('Entreprise Test Bon');
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

    private function createTestClient($em, User $user, ?string $email = 'client.bon@example.com'): Client
    {
        $client = new Client();
        $client->setNom('Dupont');
        $client->setPrenom('Jean');
        $client->setEmail($email ?? '');
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

    private function createTestDevis($em, Entreprise $entreprise, ?Client $client, string $numero = 'DEV-TEST-BON-0001'): Devis
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
        if ($client) {
            $devis->setClient($client);
        }
        $devis->setEtat('valide');
        $em->persist($devis);
        $em->flush();

        return $devis;
    }

    private function createTestBon($em, Entreprise $entreprise, ?Devis $devis, string $numero = 'BC-TEST-0001'): BonDeCommande
    {
        $bon = new BonDeCommande();
        $bon->setNumeroBon($numero);
        $bon->setDateCreation(new \DateTime());
        $bon->setMontantHT(1000.0);
        $bon->setTauxTVA(20.0);
        $bon->setMontantTtc(1200.0);
        $bon->setDescription('Bon de commande de test');
        $bon->setEntreprise($entreprise);
        if ($devis) {
            $bon->setDevis($devis);
        }
        $bon->setEtat('en_attente');
        $em->persist($bon);
        $em->flush();

        return $bon;
    }

    private function cleanUpAll($em, string $email): void
    {
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return;
        }

        $entreprises = $em->getRepository(Entreprise::class)->findBy(['user' => $user]);
        foreach ($entreprises as $entreprise) {
            $factures = $em->getRepository(Facture::class)->findBy(['entreprise' => $entreprise]);
            foreach ($factures as $facture) {
                $em->remove($facture);
            }
            $bons = $em->getRepository(BonDeCommande::class)->findBy(['entreprise' => $entreprise]);
            foreach ($bons as $bon) {
                $em->remove($bon);
            }
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

    public function testBonIndexRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/bon-de-commande');

        $this->assertResponseRedirects();
    }

    public function testBonIndexLoadsSuccessfullyWhenAuthenticated(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.bon.index@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);
        $this->createTestBon($em, $entreprise, null);

        $client->loginUser($user);
        $client->request('GET', '/bon-de-commande');

        $this->assertResponseIsSuccessful();

        $this->cleanUpAll($em, $email);
    }

    public function testBonShowDisplaysExistingBon(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.bon.show@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);
        $bon = $this->createTestBon($em, $entreprise, null);

        $client->loginUser($user);
        $client->request('GET', '/bon-de-commande/' . $bon->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', $bon->getNumeroBon());

        $this->cleanUpAll($em, $email);
    }

    public function testBonShowReturns404ForNonExistentBon(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.bon.show404@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);

        $client->loginUser($user);
        $client->request('GET', '/bon-de-commande/999999');

        $this->assertResponseStatusCodeSame(404);

        $this->cleanUpAll($em, $email);
    }

    public function testBonPdfGeneratesValidPdfResponse(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.bon.pdf@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);
        $bon = $this->createTestBon($em, $entreprise, null, 'BC-TEST-PDF-0001');

        $client->loginUser($user);
        $client->request('GET', '/bon-de-commande/' . $bon->getId() . '/pdf');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/pdf');

        $this->cleanUpAll($em, $email);
    }

    public function testBonEnvoyerWithClientEmailSendsMessage(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.bon.envoyer@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);
        $testClient = $this->createTestClient($em, $user);
        $devis = $this->createTestDevis($em, $entreprise, $testClient, 'DEV-TEST-BON-ENVOYER-0001');
        $bon = $this->createTestBon($em, $entreprise, $devis, 'BC-TEST-ENVOYER-0001');

        $client->loginUser($user);
        $client->request('GET', '/bon-de-commande/' . $bon->getId() . '/envoyer');

        $this->assertResponseRedirects('/bon-de-commande/' . $bon->getId());
        $this->assertCount(2, self::getMailerEvents());

        $this->cleanUpAll($em, $email);
    }

    public function testBonEnvoyerWithoutClientEmailShowsError(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.bon.envoyersansemail@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);
        $bon = $this->createTestBon($em, $entreprise, null, 'BC-TEST-NOEMAIL-0001');

        $client->loginUser($user);
        $client->request('GET', '/bon-de-commande/' . $bon->getId() . '/envoyer');

        $this->assertResponseRedirects('/bon-de-commande/' . $bon->getId());
        $this->assertCount(0, self::getMailerEvents(), 'Aucun email ne devrait avoir été envoyé sans email client.');

        $this->cleanUpAll($em, $email);
    }

    public function testBonPayerGeneratesInvoiceAndUpdatesEtat(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.bon.payer@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);
        $bon = $this->createTestBon($em, $entreprise, null, 'BC-TEST-PAYER-0001');
        $bonId = $bon->getId();

        $this->assertEquals('en_attente', $bon->getEtat());

        $client->loginUser($user);
        $client->request('GET', '/bon-de-commande/' . $bonId . '/payer');

        $this->assertResponseRedirects();

        $em->clear();
        $updatedBon = $em->getRepository(BonDeCommande::class)->find($bonId);
        $this->assertEquals('paye', $updatedBon->getEtat());

        $facture = $em->getRepository(Facture::class)->findOneBy(['bonDeCommande' => $updatedBon]);
        $this->assertNotNull($facture, 'Une facture devrait avoir été générée automatiquement.');
        $this->assertEquals('impayee', $facture->getEtat());
        $this->assertEquals(1000.0, $facture->getMontantHT());

        $this->cleanUpAll($em, $email);
    }

    public function testBonPayerOnAlreadyPaidBonDoesNotDuplicateInvoice(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.bon.dejapaye@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);
        $bon = $this->createTestBon($em, $entreprise, null, 'BC-TEST-DEJAPAYE-0001');
        $bon->setEtat('paye');
        $em->flush();
        $bonId = $bon->getId();

        $client->loginUser($user);
        $client->request('GET', '/bon-de-commande/' . $bonId . '/payer');

        $this->assertResponseRedirects('/bon-de-commande/' . $bonId);

        $facturesCount = count($em->getRepository(Facture::class)->findBy(['bonDeCommande' => $bon]));
        $this->assertEquals(0, $facturesCount, 'Aucune facture ne devrait être créée si déjà payé.');

        $this->cleanUpAll($em, $email);
    }
}