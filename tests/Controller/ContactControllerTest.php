<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ContactControllerTest extends WebTestCase
{
    public function testContactPageLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $client->request('GET', '/contact');

        $this->assertResponseIsSuccessful();
    }

    public function testContactFormWithValidDataSendsEmail(): void
    {
        $client = static::createClient();

        $client->request('GET', '/contact');
        $client->submitForm('Envoyer', [
            'email' => 'visiteur@example.com',
            'sujet' => 'Question test',
            'message' => 'Ceci est un message de test.',
            'terms' => true,
        ]);

        $this->assertCount(2, self::getMailerEvents());    }

    public function testContactFormWithInvalidEmailShowsError(): void
    {
        $client = static::createClient();

        $client->request('GET', '/contact');
        $client->submitForm('Envoyer', [
            'email' => 'pas-un-email',
            'sujet' => 'Question test',
            'message' => 'Ceci est un message de test.',
            'terms' => true,
        ]);

        $this->assertCount(0, self::getMailerEvents());
    }

    public function testContactFormWithMissingFieldsShowsError(): void
    {
        $client = static::createClient();

        $client->request('GET', '/contact');
        $client->submitForm('Envoyer', [
            'email' => '',
            'sujet' => '',
            'message' => '',
            'terms' => true,
        ]);

        $this->assertCount(0, self::getMailerEvents());
    }

public function testContactFormRateLimitBlocksAfterRepeatedSubmissions(): void
    {
        $client = static::createClient();

        $formData = [
            'email' => 'spammer.' . uniqid() . '@example.com',
            'sujet' => 'Test rate limit',
            'message' => 'Message répété.',
            'terms' => true,
        ];

        // Les 3 premières soumissions doivent réussir (2 events : queued + sent)
        for ($i = 0; $i < 3; $i++) {
            $client->request('GET', '/contact');
            $client->submitForm('Envoyer', $formData);
            $this->assertCount(2, self::getMailerEvents(), "La tentative " . ($i + 1) . " devrait réussir.");
        }

        // La 4e soumission doit être bloquée par la limite (0 event)
        $client->request('GET', '/contact');
        $client->submitForm('Envoyer', $formData);
        $this->assertCount(0, self::getMailerEvents(), 'La 4e tentative devrait être bloquée par la limite de 3/heure.');
    }
}