<?php

namespace App\Tests\Controller;

use App\Entity\RegistrationToken;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationMultiStepControllerTest extends WebTestCase
{
    private function cleanUpToken($em, string $email): void
    {
        $tokens = $em->getRepository(RegistrationToken::class)->findBy(['email' => $email]);
        foreach ($tokens as $token) {
            $em->remove($token);
        }
        $em->flush();
    }

    private function cleanUpUser($em, string $email): void
    {
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($user) {
            $em->remove($user);
            $em->flush();
        }
    }

    public function testStep1PageLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $client->request('GET', '/register/step1');

        $this->assertResponseIsSuccessful();
    }

    public function testStep1WithValidEmailCreatesTokenAndSendsEmail(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $email = 'test.register.step1@example.com';
        $this->cleanUpToken($em, $email);
        $this->cleanUpUser($em, $email);

        $client->request('POST', '/register/step1', [
            'email' => $email,
        ]);

        $this->assertResponseRedirects('/register/step1');
        $this->assertCount(2, self::getMailerEvents());

        $token = $em->getRepository(RegistrationToken::class)->findOneBy(['email' => $email]);
        $this->assertNotNull($token, 'Un token devrait avoir été créé.');

        $this->cleanUpToken($em, $email);
    }

    public function testStep1WithInvalidEmailShowsError(): void
    {
        $client = static::createClient();

        $client->request('POST', '/register/step1', [
            'email' => 'pas-un-email',
        ]);

        $this->assertResponseRedirects('/register/step1');
        $this->assertCount(0, self::getMailerEvents());
    }

    public function testStep1WithAlreadyUsedEmailShowsError(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $email = 'test.register.dejautilise@example.com';
        $this->cleanUpUser($em, $email);

        $existingUser = new User();
        $existingUser->setEmail($email);
        $existingUser->setPassword('peu-importe-le-hash');
        $em->persist($existingUser);
        $em->flush();

        $client->request('POST', '/register/step1', [
            'email' => $email,
        ]);

        $this->assertResponseRedirects('/register/step1');
        $this->assertCount(0, self::getMailerEvents());

        $this->cleanUpUser($em, $email);
    }

    public function testStep2WithValidTokenPageLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $email = 'test.register.step2page@example.com';
        $this->cleanUpToken($em, $email);

        $token = new RegistrationToken();
        $token->setEmail($email);
        $token->setToken('valid-reg-token-123');
        $token->setExpiresAt((new \DateTime())->modify('+1 hour'));
        $em->persist($token);
        $em->flush();

        $client->request('GET', '/register/step2/valid-reg-token-123');

        $this->assertResponseIsSuccessful();

        $this->cleanUpToken($em, $email);
    }

    public function testStep2WithExpiredTokenRedirectsToStep1(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $email = 'test.register.step2expired@example.com';
        $this->cleanUpToken($em, $email);

        $token = new RegistrationToken();
        $token->setEmail($email);
        $token->setToken('expired-reg-token-123');
        $token->setExpiresAt((new \DateTime())->modify('-1 hour'));
        $em->persist($token);
        $em->flush();

        $client->request('GET', '/register/step2/expired-reg-token-123');

        $this->assertResponseRedirects('/register/step1');

        $this->cleanUpToken($em, $email);
    }

    public function testStep2WithUnknownTokenRedirectsToStep1(): void
    {
        $client = static::createClient();

        $client->request('GET', '/register/step2/ce-token-n-existe-pas');

        $this->assertResponseRedirects('/register/step1');
    }

    public function testStep2WithMismatchedPasswordsShowsError(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $email = 'test.register.mismatch@example.com';
        $this->cleanUpToken($em, $email);
        $this->cleanUpUser($em, $email);

        $token = new RegistrationToken();
        $token->setEmail($email);
        $token->setToken('mismatch-token-123');
        $token->setExpiresAt((new \DateTime())->modify('+1 hour'));
        $em->persist($token);
        $em->flush();

        $client->request('POST', '/register/step2/mismatch-token-123', [
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'password' => 'Password123!',
            'password_confirm' => 'AutreMotDePasse!',
        ]);

        $this->assertResponseRedirects('/register/step2/mismatch-token-123');

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        $this->assertNull($user, 'Aucun utilisateur ne devrait être créé si les mots de passe ne correspondent pas.');

        $this->cleanUpToken($em, $email);
    }

    public function testStep2WithShortPasswordShowsError(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $email = 'test.register.shortpass@example.com';
        $this->cleanUpToken($em, $email);
        $this->cleanUpUser($em, $email);

        $token = new RegistrationToken();
        $token->setEmail($email);
        $token->setToken('shortpass-token-123');
        $token->setExpiresAt((new \DateTime())->modify('+1 hour'));
        $em->persist($token);
        $em->flush();

        $client->request('POST', '/register/step2/shortpass-token-123', [
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'password' => 'short',
            'password_confirm' => 'short',
        ]);

        $this->assertResponseRedirects('/register/step2/shortpass-token-123');

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        $this->assertNull($user, 'Aucun utilisateur ne devrait être créé si le mot de passe est trop court.');

        $this->cleanUpToken($em, $email);
    }

    public function testStep2WithValidDataCreatesUserAndDeletesToken(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $email = 'test.register.success@example.com';
        $this->cleanUpToken($em, $email);
        $this->cleanUpUser($em, $email);

        $token = new RegistrationToken();
        $token->setEmail($email);
        $token->setToken('success-token-123');
        $token->setExpiresAt((new \DateTime())->modify('+1 hour'));
        $em->persist($token);
        $em->flush();

        $client->request('POST', '/register/step2/success-token-123', [
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'telephone' => '0612345678',
            'password' => 'ValidPassword123!',
            'password_confirm' => 'ValidPassword123!',
        ]);

        $this->assertResponseRedirects('/login');

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        $this->assertNotNull($user, 'L\'utilisateur devrait avoir été créé.');
        $this->assertEquals('Dupont', $user->getNom());
        $this->assertTrue($user->isVerified());

        $remainingToken = $em->getRepository(RegistrationToken::class)->findOneBy(['token' => 'success-token-123']);
        $this->assertNull($remainingToken, 'Le token devrait avoir été supprimé après usage.');

        $this->cleanUpUser($em, $email);
    }
}