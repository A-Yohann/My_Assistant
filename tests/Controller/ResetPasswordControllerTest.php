<?php

namespace App\Tests\Controller;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ResetPasswordControllerTest extends WebTestCase
{
    private function cleanUp($em, string $email): void
    {
        $token = $em->getRepository(PasswordResetToken::class)->findOneBy(['email' => $email]);
        if ($token) {
            $em->remove($token);
        }
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($user) {
            $em->remove($user);
        }
        $em->flush();
    }

    public function testResetPasswordWithValidTokenSucceeds(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.reset.valid@example.com';
        $this->cleanUp($em, $email);

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, 'OldPassword123!'));
        $em->persist($user);

        $resetToken = new PasswordResetToken();
        $resetToken->setEmail($email);
        $resetToken->setToken('valid-token-12345');
        $resetToken->setExpiresAt((new \DateTime())->modify('+1 hour'));
        $em->persist($resetToken);
        $em->flush();

        $client->request('GET', '/reset-password/valid-token-12345');
        $this->assertResponseIsSuccessful();

        $client->request('POST', '/reset-password/valid-token-12345', [
            'password' => 'NewPassword456!',
        ]);

        $this->assertResponseRedirects('/login');

        // Le token doit avoir été supprimé après usage
        $tokenAfter = $em->getRepository(PasswordResetToken::class)->findOneBy(['token' => 'valid-token-12345']);
        $this->assertNull($tokenAfter, 'Le token devrait être supprimé après réinitialisation réussie.');

        // Le mot de passe doit avoir changé
        $em->clear();
        $updatedUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        $this->assertTrue(
            $passwordHasher->isPasswordValid($updatedUser, 'NewPassword456!'),
            'Le nouveau mot de passe devrait être valide.'
        );

        $this->cleanUp($em, $email);
    }

    public function testResetPasswordWithExpiredTokenRedirects(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        $email = 'test.reset.expired@example.com';
        $this->cleanUp($em, $email);

        $resetToken = new PasswordResetToken();
        $resetToken->setEmail($email);
        $resetToken->setToken('expired-token-12345');
        $resetToken->setExpiresAt((new \DateTime())->modify('-1 hour'));
        $em->persist($resetToken);
        $em->flush();

        $client->request('GET', '/reset-password/expired-token-12345');
        $this->assertResponseRedirects('/forgot-password');

        $this->cleanUp($em, $email);
    }

    public function testResetPasswordWithUnknownTokenRedirects(): void
    {
        $client = static::createClient();

        $client->request('GET', '/reset-password/this-token-does-not-exist');
        $this->assertResponseRedirects('/forgot-password');
    }
}