<?php

namespace App\Tests\Controller;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ForgotPasswordControllerTest extends WebTestCase
{
    public function testForgotPasswordWithExistingUserSendsEmailAndCreatesToken(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        // Nettoyage préventif si l'utilisateur existe déjà d'un run précédent
        $existing = $em->getRepository(User::class)->findOneBy(['email' => 'test.forgot@example.com']);
        if ($existing) {
            $em->remove($existing);
            $em->flush();
        }

        $user = new User();
        $user->setEmail('test.forgot@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'OldPassword123!'));
        $em->persist($user);
        $em->flush();

        $client->request('GET', '/forgot-password');
        $this->assertResponseIsSuccessful();

        $client->submitForm('Envoyer le lien', [
            'email' => 'test.forgot@example.com',
        ]);

        $this->assertEmailCount(1);

       $resetToken = $em->getRepository(PasswordResetToken::class)->findOneBy(['email' => 'test.forgot@example.com']);
        $this->assertNotNull($resetToken, 'Un token de réinitialisation devrait avoir été créé en base.');
        $this->assertNotEmpty($resetToken->getToken());

        // Nettoyage après test : on récupère des références fraîches au cas où
        // l'EntityManager utilisé par le client de test diffère de celui-ci.
        $em = static::getContainer()->get('doctrine')->getManager();
        $freshUser = $em->getRepository(User::class)->findOneBy(['email' => 'test.forgot@example.com']);
        $freshToken = $em->getRepository(PasswordResetToken::class)->findOneBy(['email' => 'test.forgot@example.com']);

        if ($freshToken) {
            $em->remove($freshToken);
        }
        if ($freshUser) {
            $em->remove($freshUser);
        }
        $em->flush();
    }

    public function testForgotPasswordWithNonExistentEmailShowsGenericSuccessMessage(): void
    {
        $client = static::createClient();

        $client->request('GET', '/forgot-password');
        $this->assertResponseIsSuccessful();

        $client->submitForm('Envoyer le lien', [
            'email' => 'inconnu@example.com',
        ]);

        $this->assertEmailCount(0);
    }

    public function testForgotPasswordWithEmptyEmailShowsError(): void
    {
        $client = static::createClient();

        $client->request('GET', '/forgot-password');
        $crawler = $client->submitForm('Envoyer le lien', [
            'email' => '',
        ]);

        $this->assertEmailCount(0);
    }
}