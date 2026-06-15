<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserControllerTest extends AbstractWebTestCase
{
    public function testRegularUserCannotAccessUserAdmin(): void
    {
        $this->loginAs('marie@huttopia.com');
        $this->client->request('GET', '/admin/users');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCanListUsers(): void
    {
        $this->loginAs('admin@huttopia.com');
        $this->client->request('GET', '/admin/users');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Utilisateurs');
        self::assertSelectorTextContains('table', 'marie@huttopia.com');
    }

    public function testAdminCreatesAdminUserAndItIsPersisted(): void
    {
        $this->loginAs('admin@huttopia.com');
        $crawler = $this->client->request('GET', '/admin/users/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Créer le compte')->form();
        $form['user[fullName]'] = 'Nadia Nouvelle';
        $form['user[email]'] = 'nadia@huttopia.com';
        $form['user[role]'] = 'ROLE_ADMIN';
        $form['user[plainPassword]'] = 'motdepasse123';
        $this->client->submit($form);

        self::assertResponseRedirects('/admin/users');

        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'nadia@huttopia.com']);
        self::assertNotNull($user);
        self::assertTrue($user->isAdmin(), 'Le compte devrait être administrateur.');

        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertTrue($hasher->isPasswordValid($user, 'motdepasse123'), 'Le mot de passe devrait être correctement haché.');
    }

    public function testNewlyCreatedUserCanLogIn(): void
    {
        // 1. L'admin crée un utilisateur classique.
        $this->loginAs('admin@huttopia.com');
        $crawler = $this->client->request('GET', '/admin/users/new');
        $form = $crawler->selectButton('Créer le compte')->form();
        $form['user[fullName]'] = 'Olivier Test';
        $form['user[email]'] = 'olivier@huttopia.com';
        $form['user[role]'] = 'ROLE_USER';
        $form['user[plainPassword]'] = 'secret123';
        $this->client->submit($form);
        self::assertResponseRedirects('/admin/users');

        // 2. Déconnexion de l'admin.
        $this->client->request('GET', '/logout');

        // 3. Le nouvel utilisateur se connecte réellement via le formulaire.
        $crawler = $this->client->request('GET', '/login');
        $loginForm = $crawler->selectButton('Se connecter')->form([
            '_username' => 'olivier@huttopia.com',
            '_password' => 'secret123',
        ]);
        $this->client->submit($loginForm);

        self::assertResponseRedirects('/projects');
    }

    public function testAdminCannotDeleteOwnAccount(): void
    {
        $admin = $this->loginAs('admin@huttopia.com');
        $adminId = $admin->getId();

        // La garde « pas de suppression de soi-même » s'exécute avant la vérif CSRF.
        $this->client->request('POST', \sprintf('/admin/users/%d/delete', $adminId));

        self::assertResponseRedirects('/admin/users');
        self::assertNotNull(
            static::getContainer()->get(UserRepository::class)->find($adminId),
            'L’admin ne doit pas pouvoir supprimer son propre compte.'
        );
    }

    public function testAdminCanDeleteAnotherUser(): void
    {
        $this->loginAs('admin@huttopia.com');
        $paul = $this->user('paul@huttopia.com');
        $paulId = $paul->getId();

        $crawler = $this->client->request('GET', '/admin/users');
        $form = $crawler->filter(\sprintf('form[action="/admin/users/%d/delete"]', $paulId))->form();
        $this->client->submit($form);

        self::assertResponseRedirects('/admin/users');
        self::assertNull(
            static::getContainer()->get(UserRepository::class)->find($paulId),
            'L’utilisateur aurait dû être supprimé.'
        );
    }
}
