<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class StripeWebhookControllerTest extends WebTestCase
{
    /**
     * Génère une signature Stripe valide localement, sans appeler l'API Stripe.
     * Reproduit l'algorithme officiel : HMAC-SHA256(secret, "{timestamp}.{payload}")
     * encodé sous la forme attendue par Stripe\Webhook::constructEvent().
     */
    private function buildStripeSignatureHeader(string $payload, string $secret): string
    {
        $timestamp = time();
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        return 't=' . $timestamp . ',v1=' . $signature;
    }

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

    private function cleanUpAll($em, string $email): void
    {
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($user) {
            $em->remove($user);
            $em->flush();
        }
    }

    public function testWebhookWithInvalidSignatureReturns400(): void
    {
        $client = static::createClient();

        $payload = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => []]]);

        $client->request(
            'POST',
            '/stripe/webhook',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => 't=1234567890,v1=signature_invalide_volontairement',
            ],
            $payload
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testWebhookCheckoutSessionCompletedUpgradesUserToPro(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.stripe.checkout@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $this->assertEquals('free', $user->getPlan());

        $payload = json_encode([
            'id' => 'evt_test_checkout',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'customer' => 'cus_test_123',
                    'subscription' => 'sub_test_123',
                    'customer_details' => [
                        'email' => $email,
                    ],
                ],
            ],
        ]);

        $secret = $_ENV['STRIPE_WEBHOOK_SECRET'];
        $signature = $this->buildStripeSignatureHeader($payload, $secret);

        $client->request(
            'POST',
            '/stripe/webhook',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload
        );

        $this->assertResponseIsSuccessful();
        $this->assertEquals('OK', $client->getResponse()->getContent());

        $em->clear();
        $updatedUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        $this->assertEquals('pro', $updatedUser->getPlan());
        $this->assertEquals('cus_test_123', $updatedUser->getStripeCustomerId());
        $this->assertEquals('sub_test_123', $updatedUser->getStripeSubscriptionId());

        $this->assertCount(2, self::getMailerEvents());

        $this->cleanUpAll($em, $email);
    }

    public function testWebhookCheckoutSessionCompletedWithUnknownEmailDoesNothing(): void
    {
        $client = static::createClient();

        $payload = json_encode([
            'id' => 'evt_test_unknown',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'customer' => 'cus_test_unknown',
                    'subscription' => 'sub_test_unknown',
                    'customer_details' => [
                        'email' => 'cet.email.n.existe.pas@example.com',
                    ],
                ],
            ],
        ]);

        $secret = $_ENV['STRIPE_WEBHOOK_SECRET'];
        $signature = $this->buildStripeSignatureHeader($payload, $secret);

        $client->request(
            'POST',
            '/stripe/webhook',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload
        );

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, self::getMailerEvents(), 'Aucun email ne devrait être envoyé si l\'utilisateur n\'existe pas.');
    }

    public function testWebhookSubscriptionDeletedDowngradesUserToFree(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.stripe.subdeleted@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $user->setPlan('pro');
        $user->setStripeCustomerId('cus_test_456');
        $user->setStripeSubscriptionId('sub_test_456');
        $em->flush();

        $payload = json_encode([
            'id' => 'evt_test_subdeleted',
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'customer' => 'cus_test_456',
                    'status' => 'canceled',
                ],
            ],
        ]);

        $secret = $_ENV['STRIPE_WEBHOOK_SECRET'];
        $signature = $this->buildStripeSignatureHeader($payload, $secret);

        $client->request(
            'POST',
            '/stripe/webhook',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload
        );

        $this->assertResponseIsSuccessful();

        $em->clear();
        $updatedUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        $this->assertEquals('free', $updatedUser->getPlan());
        $this->assertNull($updatedUser->getStripeSubscriptionId());

        $this->cleanUpAll($em, $email);
    }

    public function testWebhookSubscriptionUpdatedWithActiveStatusKeepsProPlan(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $email = 'test.stripe.subactive@example.com';
        $this->cleanUpAll($em, $email);

        $user = $this->createTestUser($em, $passwordHasher, $email);
        $user->setPlan('pro');
        $user->setStripeCustomerId('cus_test_789');
        $user->setStripeSubscriptionId('sub_test_789');
        $em->flush();

        $payload = json_encode([
            'id' => 'evt_test_subactive',
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'customer' => 'cus_test_789',
                    'status' => 'active',
                ],
            ],
        ]);

        $secret = $_ENV['STRIPE_WEBHOOK_SECRET'];
        $signature = $this->buildStripeSignatureHeader($payload, $secret);

        $client->request(
            'POST',
            '/stripe/webhook',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload
        );

        $this->assertResponseIsSuccessful();

        $em->clear();
        $updatedUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        $this->assertEquals('pro', $updatedUser->getPlan(), 'Le plan ne devrait pas changer pour un statut actif.');

        $this->cleanUpAll($em, $email);
    }

    public function testWebhookWithUnhandledEventTypeReturnsOk(): void
    {
        $client = static::createClient();

        $payload = json_encode([
            'id' => 'evt_test_unhandled',
            'type' => 'invoice.payment_succeeded',
            'data' => [
                'object' => [],
            ],
        ]);

        $secret = $_ENV['STRIPE_WEBHOOK_SECRET'];
        $signature = $this->buildStripeSignatureHeader($payload, $secret);

        $client->request(
            'POST',
            '/stripe/webhook',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            $payload
        );

        $this->assertResponseIsSuccessful();
    }
}