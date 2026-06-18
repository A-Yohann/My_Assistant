<?php

namespace App\Tests\Controller;

use App\Entity\Entreprise;
use App\Entity\Facture;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class FactureControllerTest extends WebTestCase
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
        $entreprise->setNomEntreprise('Entreprise Test Facture');
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

    private function createTestFacture($em, Entreprise $entreprise, string $numero = 'FAC-TEST-0001'): Facture
    {
        $facture = new Facture();
        $facture->setNumeroFacture($numero);
        $facture->setDateCreation(new \DateTime());
        $facture->setDateEcheance((new \DateTime())->modify('+30 days'));
        $facture->setMontantHT(1000.0);
        $facture->setTauxTVA(20.0);
        $facture->setMontantTtc(1200.0);
        $facture->setDescription('Facture de test');
        $facture->setEntreprise($entreprise);
        $facture->setEtat('impayee');
        $em->persist($facture);
        $em->flush();

        return $facture;
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
            $em->remove($entreprise);
        }

        $em->remove($user);
        $em->flush();
    }

    public function testFactureIndexRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/facture');

        $this->assertResponseRedirects();
    }

    public function testFactureIndexLoadsSuccessfullyWhenAuthenticated(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.facture.index@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $this->createTestEntreprise($em, $user);

        $client->loginUser($user);
        $client->request('GET', '/facture');

        $this->assertResponseIsSuccessful();

        $this->cleanUpAll($em, $email);
    }

    public function testFactureShowDisplaysExistingFacture(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.facture.show@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);
        $facture = $this->createTestFacture($em, $entreprise);

        $client->loginUser($user);
        $client->request('GET', '/facture/' . $facture->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', $facture->getNumeroFacture());

        $this->cleanUpAll($em, $email);
    }

    public function testFactureShowReturns404ForNonExistentFacture(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.facture.show404@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);

        $client->loginUser($user);
        $client->request('GET', '/facture/999999');

        $this->assertResponseStatusCodeSame(404);

        $this->cleanUpAll($em, $email);
    }

    public function testFacturePdfGeneratesValidPdfResponse(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.facture.pdf@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);
        $facture = $this->createTestFacture($em, $entreprise, 'FAC-TEST-PDF-0001');

        $client->loginUser($user);
        $client->request('GET', '/facture/' . $facture->getId() . '/pdf');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/pdf');

        $this->cleanUpAll($em, $email);
    }

    public function testFacturePayerMarksFactureAsPaid(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.facture.payer@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);
        $facture = $this->createTestFacture($em, $entreprise, 'FAC-TEST-PAYER-0001');
        $factureId = $facture->getId();

        $this->assertEquals('impayee', $facture->getEtat());

        $client->loginUser($user);
        $client->request('GET', '/facture/' . $factureId . '/payer');

        $this->assertResponseRedirects('/facture/' . $factureId);

        $em->clear();
        $updatedFacture = $em->getRepository(Facture::class)->find($factureId);
        $this->assertEquals('payee', $updatedFacture->getEtat());

        $this->cleanUpAll($em, $email);
    }

    public function testFacturePayerOnAlreadyPaidFactureShowsInfoMessage(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.facture.dejapayee@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);
        $facture = $this->createTestFacture($em, $entreprise, 'FAC-TEST-DEJAPAYEE-0001');
        $facture->setEtat('payee');
        $em->flush();
        $factureId = $facture->getId();

        $client->loginUser($user);
        $client->request('GET', '/facture/' . $factureId . '/payer');

        $this->assertResponseRedirects('/facture/' . $factureId);

        $em->clear();
        $updatedFacture = $em->getRepository(Facture::class)->find($factureId);
        $this->assertEquals('payee', $updatedFacture->getEtat());

        $this->cleanUpAll($em, $email);
    }
}