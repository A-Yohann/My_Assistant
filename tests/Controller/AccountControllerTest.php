<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AccountControllerTest extends WebTestCase
{
    private function createTestUser($em, $passwordHasher, string $email, array $roles = []): User
    {
        $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            $em->remove($existing);
            $em->flush();
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, 'TestPassword123!'));
        $user->setRoles($roles);
        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function cleanUpAll($em, string $email): void
    {
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($user) {
            $em->remove($user);
            $em->flush();
        }
    }

    public function testAccountShowRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/account');

        $this->assertResponseRedirects();
    }

    public function testAccountShowDisplaysUserInfo(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.account.show@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);

        $client->loginUser($user);
        $client->request('GET', '/account');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', $email);

        $this->cleanUpAll($em, $email);
    }

    public function testAccountManagePageLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.account.manage@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);

        $client->loginUser($user);
        $client->request('GET', '/account/manage');

        $this->assertResponseIsSuccessful();

        $this->cleanUpAll($em, $email);
    }

    public function testAccountManageUpdatesNomAndPrenom(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.account.update@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);

        $client->loginUser($user);
        $client->request('POST', '/account/manage', [
            'nom' => 'Dupont',
            'prenom' => 'Jean',
        ]);

        $this->assertResponseRedirects('/account/manage');

        $em->clear();
        $updatedUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        $this->assertEquals('Dupont', $updatedUser->getNom());
        $this->assertEquals('Jean', $updatedUser->getPrenom());

        $this->cleanUpAll($em, $email);
    }

    public function testAccountManageWithShortPasswordShowsError(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.account.shortpassword@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $originalPasswordHash = $user->getPassword();

        $client->loginUser($user);
        $client->request('POST', '/account/manage', [
            'password' => 'abc',
        ]);

        $this->assertResponseRedirects('/account/manage');

        $em->clear();
        $updatedUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        $this->assertEquals($originalPasswordHash, $updatedUser->getPassword(), 'Le mot de passe ne devrait pas avoir changé si trop court.');

        $this->cleanUpAll($em, $email);
    }

    public function testAccountManageWithValidPasswordUpdatesIt(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.account.validpassword@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);

        $client->loginUser($user);
        $client->request('POST', '/account/manage', [
            'password' => 'NewPassword456!',
        ]);

        $this->assertResponseRedirects('/account/manage');

        $em->clear();
        $updatedUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        $this->assertTrue($passwordHasher->isPasswordValid($updatedUser, 'NewPassword456!'));

        $this->cleanUpAll($em, $email);
    }

    public function testAccountManageDeleteRemovesNonAdminAccount(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.account.delete@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $userId = $user->getId();

        $client->loginUser($user);
        $client->request('POST', '/account/manage', [
            'action' => 'delete',
        ]);

        $this->assertResponseRedirects();

        $em->clear();
        $deletedUser = $em->getRepository(User::class)->find($userId);
        $this->assertNull($deletedUser, 'Le compte devrait avoir été supprimé.');
    }

    public function testAccountManageDeleteBlockedForAdminAccount(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.account.admin@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email, ['ROLE_ADMIN']);
        $userId = $user->getId();

        $client->loginUser($user);
        $client->request('POST', '/account/manage', [
            'action' => 'delete',
        ]);

        $this->assertResponseRedirects('/account/manage');

        $em->clear();
        $stillExistingUser = $em->getRepository(User::class)->find($userId);
        $this->assertNotNull($stillExistingUser, 'Le compte admin ne devrait pas avoir été supprimé.');

        $this->cleanUpAll($em, $email);
    }
}