<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Repository\ProjectRepository;

class ProjectControllerTest extends AbstractWebTestCase
{
    public function testRegularUserDoesNotSeeNewProjectButton(): void
    {
        $this->loginAs('marie@huttopia.com');
        $this->client->request('GET', '/projects');

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('a[href="/projects/new"]');
    }

    public function testAdminSeesNewProjectButton(): void
    {
        $this->loginAs('admin@huttopia.com');
        $this->client->request('GET', '/projects');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('a[href="/projects/new"]');
    }

    public function testRegularUserCannotAccessNewProject(): void
    {
        $this->loginAs('marie@huttopia.com');
        $this->client->request('GET', '/projects/new');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCreatesProjectWithDefaultColumns(): void
    {
        $this->loginAs('admin@huttopia.com');
        $crawler = $this->client->request('GET', '/projects/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Créer le projet')->form([
            'project[name]' => 'Projet de test',
            'project[code]' => 'ZZZ',
            'project[description]' => 'Créé par un test fonctionnel.',
        ]);
        $this->client->submit($form);

        // Redirection vers le tableau du projet nouvellement créé.
        self::assertResponseRedirects();

        $project = static::getContainer()->get(ProjectRepository::class)->findOneBy(['code' => 'ZZZ']);
        self::assertNotNull($project, 'Le projet n’a pas été créé.');
        self::assertSame('Projet de test', $project->getName());
        self::assertSame('admin@huttopia.com', $project->getOwner()->getEmail());
        // Trois colonnes par défaut : À faire / En cours / Terminé.
        self::assertCount(3, $project->getColumns());
    }

    public function testProjectCodeMustBeUnique(): void
    {
        $this->loginAs('admin@huttopia.com');
        $crawler = $this->client->request('GET', '/projects/new');

        $form = $crawler->selectButton('Créer le projet')->form([
            'project[name]' => 'Doublon',
            'project[code]' => 'HUT', // déjà utilisé par les fixtures
            'project[description]' => '',
        ]);
        $this->client->submit($form);

        // Le formulaire est ré-affiché avec une erreur de validation (pas de redirection).
        self::assertResponseIsUnprocessable();
        self::assertSelectorExists('.form-row .error, ul li');
    }

    public function testAdminCanDeleteProject(): void
    {
        $this->loginAs('admin@huttopia.com');
        $project = $this->projectByCode('MOB');
        $id = $project->getId();

        $crawler = $this->client->request('GET', \sprintf('/projects/%d/edit', $id));
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form[action$="/delete"]')->form();
        $this->client->submit($form);

        self::assertResponseRedirects('/projects');
        self::assertNull(
            static::getContainer()->get(ProjectRepository::class)->find($id),
            'Le projet aurait dû être supprimé.'
        );
    }
}
