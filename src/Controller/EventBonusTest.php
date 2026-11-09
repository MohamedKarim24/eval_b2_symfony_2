<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EventBonusTest extends WebTestCase
{
    public function testValidateAccessSecurity(): void
    {
        $client = static::createClient();

        $client->request('GET', '/validate');
        $this->assertResponseRedirects('/login');

        $userRepository = static::getContainer()->get(UserRepository::class);
        $regularUser = $userRepository->findOneBy(['email' => 'user@test.fr']);
        $client->loginUser($regularUser);

        $client->request('GET', '/validate');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testMenuVisibility(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('a[href="/mes-evenements"]');

        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'user@test.fr']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/');
        $this->assertSelectorExists('a[href="/mes-evenements"]');
    }

    public function testEditAccessForVisitor(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/evenement/1/modifier');
        $this->assertResponseRedirects('/login');
    }

    public function testUserRegistrationChangesPlaces(): void
    {
        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'user@test.fr']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/evenement/1');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('S\'inscrire')->form();
        $client->submit($form);

        $this->assertResponseRedirects();
        $client->followRedirect();

        $this->assertSelectorExists('.alert-success');
    }
}