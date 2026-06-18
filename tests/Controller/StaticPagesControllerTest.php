<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StaticPagesControllerTest extends WebTestCase
{
    public function testHomePageLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }

    public function testAboutPageLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $client->request('GET', '/about');

        $this->assertResponseIsSuccessful();
    }

    public function testPricingPageLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $client->request('GET', '/pricing');

        $this->assertResponseIsSuccessful();
    }

    public function testMentionPageLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mention');

        $this->assertResponseIsSuccessful();
    }

    public function testErrorPageLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $client->request('GET', '/error');

        $this->assertResponseIsSuccessful();
    }

    public function testSaveCookieConsentAcceptsPostRequest(): void
    {
        $client = static::createClient();
        $client->request('POST', '/save-cookie-consent', [
            'consent' => 'accepted',
        ]);

        $this->assertTrue(
            $client->getResponse()->isSuccessful() || $client->getResponse()->isRedirection(),
            'La route de consentement cookie devrait répondre par un succès ou une redirection.'
        );
    }
}