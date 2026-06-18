<?php

namespace App\Tests\Controller;

use App\Entity\Entreprise;
use App\Entity\Note;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class NotesControllerTest extends WebTestCase
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
        $entreprise->setNomEntreprise('Entreprise Test Notes');
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

    private function createTestNote($em, User $user, Entreprise $entreprise, string $titre = 'Note Test'): Note
    {
        $note = new Note();
        $note->setTitre($titre);
        $note->setContenu('Contenu de la note de test');
        $note->setDateCreation(new \DateTime());
        $note->setDateModification(new \DateTime());
        $note->setPriorite(false);
        $note->setUser($user);
        $note->setEntreprise($entreprise);
        $em->persist($note);
        $em->flush();

        return $note;
    }

    private function cleanUpAll($em, string $email): void
    {
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return;
        }

        $notes = $em->getRepository(Note::class)->findBy(['user' => $user]);
        foreach ($notes as $note) {
            $em->remove($note);
        }

        $entreprises = $em->getRepository(Entreprise::class)->findBy(['user' => $user]);
        foreach ($entreprises as $entreprise) {
            $em->remove($entreprise);
        }

        $em->remove($user);
        $em->flush();
    }

    public function testNotesIndexRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/notes');

        $this->assertResponseRedirects();
    }

    public function testNotesIndexRedirectsWhenNoEntreprise(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.notes.noentreprise@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);

        $client->loginUser($user);
        $client->request('GET', '/notes');

        $this->assertResponseRedirects('/entreprise/');

        $this->cleanUpAll($em, $email);
    }

    public function testNotesIndexLoadsSuccessfullyWithEntreprise(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.notes.index@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);
        $this->createTestNote($em, $user, $entreprise);

        $client->loginUser($user);
        $client->request('GET', '/notes');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Note Test');

        $this->cleanUpAll($em, $email);
    }

    public function testNoteNewCreatesNoteSuccessfully(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.notes.new@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $this->createTestEntreprise($em, $user);

        $client->loginUser($user);
        $crawler = $client->request('GET', '/notes/new');

        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form')->form([
            'note[titre]' => 'Nouvelle note de test',
            'note[contenu]' => 'Contenu de ma nouvelle note',
            'note[dateCreation]' => '2026-06-17T10:00',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/notes');

        $note = $em->getRepository(Note::class)->findOneBy(['titre' => 'Nouvelle note de test']);
        $this->assertNotNull($note, 'La note devrait avoir été créée en base.');

        $this->cleanUpAll($em, $email);
    }

    public function testNoteShowDisplaysExistingNote(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.notes.show@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);
        $note = $this->createTestNote($em, $user, $entreprise);

        $client->loginUser($user);
        $client->request('GET', '/notes/' . $note->getIdNote());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Note Test');

        $this->cleanUpAll($em, $email);
    }

    public function testNoteDeleteRemovesNote(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.notes.delete@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);
        $note = $this->createTestNote($em, $user, $entreprise, 'Note a supprimer');
        $noteId = $note->getIdNote();

        $client->loginUser($user);
        $client->request('POST', '/notes/' . $noteId . '/delete');

        $this->assertResponseRedirects('/notes');

        $deletedNote = $em->getRepository(Note::class)->find($noteId);
        $this->assertNull($deletedNote, 'La note devrait avoir été supprimée.');

        $this->cleanUpAll($em, $email);
    }

    public function testNoteDeplacerWithValidDataUpdatesDate(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.notes.deplacer@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $entreprise = $this->createTestEntreprise($em, $user);
        $note = $this->createTestNote($em, $user, $entreprise, 'Note a deplacer');
        $noteId = $note->getIdNote();

        $client->loginUser($user);
        $client->request(
            'POST',
            '/notes/' . $noteId . '/deplacer',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['date' => '2026-07-01'])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);

        $em->clear();
        $updatedNote = $em->getRepository(Note::class)->find($noteId);
        $this->assertEquals('2026-07-01', $updatedNote->getDateCreation()->format('Y-m-d'));

        $this->cleanUpAll($em, $email);
    }

    public function testNoteDeplacerByAnotherUserReturns404(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $emailOwner = 'test.notes.deplacer.owner@example.com';
        $emailIntruder = 'test.notes.deplacer.intruder@example.com';
        $this->cleanUpAll($em, $emailOwner);
        $this->cleanUpAll($em, $emailIntruder);

        $owner = $this->createTestUser($em, $passwordHasher, $emailOwner);
        $entreprise = $this->createTestEntreprise($em, $owner);
        $note = $this->createTestNote($em, $owner, $entreprise, 'Note proteger deplacement');
        $noteId = $note->getIdNote();

        $intruder = $this->createTestUser($em, $passwordHasher, $emailIntruder);

        $client->loginUser($intruder);
        $client->request(
            'POST',
            '/notes/' . $noteId . '/deplacer',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['date' => '2026-07-01'])
        );

        $this->assertResponseStatusCodeSame(404);

        $this->cleanUpAll($em, $emailOwner);
        $this->cleanUpAll($em, $emailIntruder);
    }
}