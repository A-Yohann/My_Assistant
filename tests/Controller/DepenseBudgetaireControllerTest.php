<?php

namespace App\Tests\Controller;

use App\Entity\DepenseBudgetaire;
use App\Entity\Entreprise;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DepenseBudgetaireControllerTest extends WebTestCase
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
        $entreprise->setNomEntreprise('Entreprise Test Depense');
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

    private function createTestDepense($em, User $user, Entreprise $entreprise, string $libelle = 'Depense Test'): DepenseBudgetaire
    {
        $depense = new DepenseBudgetaire();
        $depense->setLibelle($libelle);
        $depense->setMontant(50.0);
        $depense->setQuantite(1);
        $depense->setDateDepense(new \DateTime());
        $depense->setCategorie('Fournitures');
        $depense->setMoyenPaiement(true);
        $depense->setUser($user);
        $depense->setEntreprise($entreprise);
        $em->persist($depense);
        $em->flush();

        return $depense;
    }

    private function cleanUpAll($em, string $email): void
    {
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return;
        }

        $depenses = $em->getRepository(DepenseBudgetaire::class)->findBy(['user' => $user]);
        foreach ($depenses as $depense) {
            $em->remove($depense);
        }

        $entreprises = $em->getRepository(Entreprise::class)->findBy(['user' => $user]);
        foreach ($entreprises as $entreprise) {
            $em->remove($entreprise);
        }

        $em->remove($user);
        $em->flush();
    }

    public function testDepenseIndexRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/depense');

        $this->assertResponseRedirects();
    }

    public function testDepenseIndexLoadsSuccessfullyWithoutEntreprise(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.depense.noentreprise@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);

        $client->loginUser($user);
        $client->request('GET', '/depense');

        $this->assertResponseIsSuccessful();

        $this->cleanUpAll($em, $email);
    }

    public function testDepenseIndexLoadsSuccessfullyWithExistingDepenses(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.depense.withdata@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);
        $this->createTestDepense($em, $user, $entreprise);

        $client->loginUser($user);
        $client->request('GET', '/depense');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Depense Test');

        $this->cleanUpAll($em, $email);
    }

    public function testDepenseCreationViaFormWithoutJustificatif(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.depense.create@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $this->createTestEntreprise($em, $user);

        $client->loginUser($user);
        $crawler = $client->request('GET', '/depense');

        $form = $crawler->filter('form')->form([
            'depense_budgetaire[libelle]' => 'Achat fournitures bureau',
            'depense_budgetaire[montant]' => '25.50',
            'depense_budgetaire[quantite]' => '2',
            'depense_budgetaire[dateDepense]' => '2026-06-17',
            'depense_budgetaire[categorie]' => 'Fournitures',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/depense');

        $depense = $em->getRepository(DepenseBudgetaire::class)->findOneBy(['libelle' => 'Achat fournitures bureau']);
        $this->assertNotNull($depense, 'La dépense devrait avoir été créée en base.');
        $this->assertEquals(25.50, $depense->getMontant());
        $this->assertEquals(2, $depense->getQuantite());

        $this->cleanUpAll($em, $email);
    }

    public function testDepenseSupprimerDeletesOwnedDepense(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.depense.supprimer@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);
        $depense = $this->createTestDepense($em, $user, $entreprise, 'Depense a supprimer');
        $depenseId = $depense->getIdDepense();

        $client->loginUser($user);
        $client->request('GET', '/depense/' . $depenseId . '/supprimer');

        $this->assertResponseRedirects('/depense');

        $deletedDepense = $em->getRepository(DepenseBudgetaire::class)->find($depenseId);
        $this->assertNull($deletedDepense, 'La dépense devrait avoir été supprimée.');

        $this->cleanUpAll($em, $email);
    }

    public function testDepenseSupprimerDoesNotDeleteOtherUsersDepense(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $emailOwner = 'test.depense.owner@example.com';
        $emailIntruder = 'test.depense.intruder@example.com';
        $this->cleanUpAll($em, $emailOwner);
        $this->cleanUpAll($em, $emailIntruder);

        $owner = $this->createTestUser($em, $passwordHasher, $emailOwner);
        $entreprise = $this->createTestEntreprise($em, $owner);
        $depense = $this->createTestDepense($em, $owner, $entreprise, 'Depense proteger');
        $depenseId = $depense->getIdDepense();

        $intruder = $this->createTestUser($em, $passwordHasher, $emailIntruder);

        $client->loginUser($intruder);
        $client->request('GET', '/depense/' . $depenseId . '/supprimer');

        $this->assertResponseRedirects('/depense');

        $em->clear();
        $stillExists = $em->getRepository(DepenseBudgetaire::class)->find($depenseId);
        $this->assertNotNull($stillExists, 'La dépense d\'un autre utilisateur ne devrait pas pouvoir être supprimée.');

        $this->cleanUpAll($em, $emailOwner);
        $this->cleanUpAll($em, $emailIntruder);
    }
}