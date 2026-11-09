<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EventControllerTest extends WebTestCase
{
    public function testHomeDisplaysEvents(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();

        $this->assertCount(3, $crawler->filter('.event-card'));

        $content = $client->getResponse()->getContent();
        $this->assertStringNotContainsString('Concert passé', $content);
        $this->assertStringNotContainsString('Tournoi de basket', $content);
    }

    public function testEventDetailClick(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();

        $link = $crawler->selectLink('Soirée jazz')->link();
        $crawler = $client->click($link);

        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('h1', 'Soirée jazz');
    }
}