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

    public function testDevisGenererPageLoadsWhenAuthenticated(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.devis.generer.page@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $this->createTestEntreprise($em, $user);

        $client->loginUser($user);
        $client->request('GET', '/devis/generer');

        $this->assertResponseIsSuccessful();

        $this->cleanUpAll($em, $email);
    }

   public function testDevisGenererWithNewClientCreatesDevisSuccessfully(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.devis.generer.new@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);

        $client->loginUser($user);
        $crawler = $client->request('GET', '/devis/generer');

        // Le bouton de soumission est de type="button" (géré en JS), pas
        // un vrai submit. On récupère donc le <form> directement et on
        // simule le comportement du JS (ajout de la signature) avant
        // de soumettre nous-mêmes la requête POST.
        $form = $crawler->filter('form')->form([
            'devis[numeroDevis]' => 'DEV-TEST-0001',
            'devis[dateEmission]' => '2026-06-17',
            'devis[dateValidite]' => '2026-07-17',
            'devis[montantHT]' => '1000',
            'devis[tva]' => '20',
            'devis[description]' => 'Prestation de test.',
            'devis[entreprise]' => (string) $entreprise->getId(),
            'devis[dateCreation]' => '2026-06-17',
            'devis[clientNom]' => 'Dupont',
            'devis[clientPrenom]' => 'Jean',
            'devis[clientEmail]' => 'jean.dupont@example.com',
            'devis[clientTelephone]' => '0612345678',
            'devis[clientCodePostal]' => '75000',
            'devis[clientVille]' => 'Paris',
            'devis[clientPays]' => 'FR',
        ]);

        $client->submit($form, [
            'signature_emetteur' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAAAAAA6fptVAAAACklEQVQYV2NgAAIAAAUAAen63NgAAAAASUVORK5CYII=',
        ]);

        $this->assertResponseRedirects('/devis');

        $devis = $em->getRepository(\App\Entity\Devis::class)->findOneBy(['numeroDevis' => 'DEV-TEST-0001']);
        $this->assertNotNull($devis, 'Le devis devrait avoir été créé en base.');
        $this->assertEquals('en_attente', $devis->getEtat());

        $this->cleanUpAll($em, $email);
    }

    private function createTestClient($em, User $user): Client
    {
        $client = new Client();
        $client->setNom('Dupont');
        $client->setPrenom('Jean');
        $client->setEmail('jean.dupont.client@example.com');
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

    private function createTestDevis($em, Entreprise $entreprise, Client $client, string $numero = 'DEV-TEST-SHOW-0001'): Devis
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

    public function testDevisShowDisplaysExistingDevis(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.devis.show@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);
        $testClient = $this->createTestClient($em, $user);
        $devis = $this->createTestDevis($em, $entreprise, $testClient);

        $client->loginUser($user);
        $client->request('GET', '/devis/' . $devis->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', $devis->getNumeroDevis());

        $this->cleanUpAll($em, $email);
    }

    public function testDevisShowReturns404ForNonExistentDevis(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.devis.show404@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);

        $client->loginUser($user);
        $client->request('GET', '/devis/999999');

        $this->assertResponseStatusCodeSame(404);

        $this->cleanUpAll($em, $email);
    }

    public function testDevisPdfGeneratesValidPdfResponse(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.devis.pdf@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);
        $testClient = $this->createTestClient($em, $user);
        $devis = $this->createTestDevis($em, $entreprise, $testClient, 'DEV-TEST-PDF-0001');

        $client->loginUser($user);
        $client->request('GET', '/devis/' . $devis->getId() . '/pdf');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/pdf');

        $this->cleanUpAll($em, $email);
    }

    public function testDevisEnvoyerSendsEmailAndGeneratesSignatureToken(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.devis.envoyer@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);
        $testClient = $this->createTestClient($em, $user);
        $devis = $this->createTestDevis($em, $entreprise, $testClient, 'DEV-TEST-ENVOYER-0001');

        $this->assertNull($devis->getSignatureToken(), 'Le token ne devrait pas exister avant envoi.');

        $client->loginUser($user);
        $client->request('GET', '/devis/' . $devis->getId() . '/envoyer');

        $this->assertResponseRedirects('/devis/' . $devis->getId());

        $this->assertCount(2, self::getMailerEvents());

        $em->refresh($devis);
        $this->assertNotNull($devis->getSignatureToken(), 'Un token de signature devrait avoir été généré.');

        $this->cleanUpAll($em, $email);
    }

    public function testDevisSignerWithValidTokenDisplaysSignaturePage(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.devis.signer@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);
        $testClient = $this->createTestClient($em, $user);
        $devis = $this->createTestDevis($em, $entreprise, $testClient, 'DEV-TEST-SIGNER-0001');
        $devis->setSignatureToken('test-signature-token-123');
        $em->flush();

        $client->request('GET', '/devis/' . $devis->getId() . '/signer/test-signature-token-123');

        $this->assertResponseIsSuccessful();

        $this->cleanUpAll($em, $email);
    }

    public function testDevisSignerWithInvalidTokenReturns404(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.devis.signerinvalid@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);
        $testClient = $this->createTestClient($em, $user);
        $devis = $this->createTestDevis($em, $entreprise, $testClient, 'DEV-TEST-SIGNERINVALID-0001');
        $devis->setSignatureToken('le-bon-token');
        $em->flush();

        $client->request('GET', '/devis/' . $devis->getId() . '/signer/mauvais-token');

        $this->assertResponseStatusCodeSame(404);

        $this->cleanUpAll($em, $email);
    }

    public function testDevisSignerConfirmerCreatesSignatureAndBonDeCommande(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.devis.signerconfirmer@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);
        $testClient = $this->createTestClient($em, $user);
        $devis = $this->createTestDevis($em, $entreprise, $testClient, 'DEV-TEST-CONFIRM-0001');
        $devis->setSignatureToken('token-confirmer-123');
        $em->flush();
        $devisId = $devis->getId();

        $client->request(
            'POST',
            '/devis/' . $devisId . '/signer/token-confirmer-123/confirmer',
            [
                'signature_image' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAAAAAA6fptVAAAACklEQVQYV2NgAAIAAAUAAen63NgAAAAASUVORK5CYII=',
            ]
        );

        $this->assertResponseIsSuccessful();

        $em->clear();
        $updatedDevis = $em->getRepository(Devis::class)->find($devisId);
        $this->assertTrue($updatedDevis->isSignature(), 'Le devis devrait être marqué comme signé.');
        $this->assertEquals('valide', $updatedDevis->getEtat());
        $this->assertNull($updatedDevis->getSignatureToken(), 'Le token devrait être invalidé après signature.');

        $bon = $em->getRepository(\App\Entity\BonDeCommande::class)->findOneBy(['devis' => $updatedDevis]);
        $this->assertNotNull($bon, 'Un bon de commande devrait avoir été créé après signature.');

        $em->remove($bon);
        $em->flush();
        $this->cleanUpAll($em, $email);
    }
}