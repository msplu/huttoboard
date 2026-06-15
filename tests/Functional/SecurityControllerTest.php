<?php

declare(strict_types=1);

namespace App\Tests\Functional;

class SecurityControllerTest extends AbstractWebTestCase
{
    public function testLoginPageIsAccessible(): void
    {
        $crawler = $this->client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="_username"]');
        self::assertSelectorExists('input[name="_password"]');
    }

    public function testAnonymousIsRedirectedToLogin(): void
    {
        $this->client->request('GET', '/projects');

        self::assertResponseRedirects('/login');
    }

    public function testLoginWithValidCredentials(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'admin@huttopia.com',
            '_password' => 'admin',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects('/projects');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Projets');
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'admin@huttopia.com',
            '_password' => 'mauvais-mot-de-passe',
        ]);
        $this->client->submit($form);

        // Redirection vers le login, puis message d'erreur affiché.
        self::assertResponseRedirects('/login');
        $crawler = $this->client->followRedirect();
        self::assertSelectorExists('.alert-error');
    }

    public function testLogout(): void
    {
        $this->loginAs('admin@huttopia.com');
        $this->client->request('GET', '/logout');

        self::assertResponseRedirects();
    }

    public function testAuthenticatedUserReachesProjects(): void
    {
        $this->loginAs('marie@huttopia.com');
        $this->client->request('GET', '/projects');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Projets');
    }
}
