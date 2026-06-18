<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityControllerTest extends WebTestCase
{
    private function cleanUp($em, string $email): void
    {
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($user) {
            $em->remove($user);
            $em->flush();
        }
    }

    public function testLoginPageLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
    }

    public function testLoginWithValidCredentialsRedirectsToDashboard(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.login.valid@example.com';
        $this->cleanUp($em, $email);

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, 'CorrectPassword123!'));
        $em->persist($user);
        $em->flush();

        $client->request('GET', '/login');
        $client->submitForm('Se connecter', [
            '_username' => $email,
            '_password' => 'CorrectPassword123!',
        ]);

        $this->assertResponseRedirects();

        $this->cleanUp($em, $email);
    }

    public function testLoginWithInvalidPasswordShowsError(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.login.invalid@example.com';
        $this->cleanUp($em, $email);

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, 'CorrectPassword123!'));
        $em->persist($user);
        $em->flush();

        $client->request('GET', '/login');
        $client->submitForm('Se connecter', [
            '_username' => $email,
            '_password' => 'WrongPassword!',
        ]);

        $this->assertResponseRedirects('/login');

        $this->cleanUp($em, $email);
    }

    public function testLoginWithNonExistentUserShowsError(): void
    {
        $client = static::createClient();

        $client->request('GET', '/login');
        $client->submitForm('Se connecter', [
            '_username' => 'inconnu.totalement@example.com',
            '_password' => 'PeuImporte123!',
        ]);

        $this->assertResponseRedirects('/login');
    }
}