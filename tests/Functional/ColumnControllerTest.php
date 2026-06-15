<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Repository\BoardColumnRepository;

class ColumnControllerTest extends AbstractWebTestCase
{
    public function testRegularUserCannotManageColumns(): void
    {
        $this->loginAs('marie@huttopia.com');
        $project = $this->projectByCode('HUT');
        $this->client->request('GET', \sprintf('/projects/%d/columns', $project->getId()));

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCanAddColumn(): void
    {
        $this->loginAs('admin@huttopia.com');
        $project = $this->projectByCode('HUT');
        $projectId = $project->getId();
        $before = \count($this->columnsOf($projectId));

        $crawler = $this->client->request('GET', \sprintf('/projects/%d/columns', $projectId));
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Ajouter')->form();
        $form['board_column[name]'] = 'Bloqué';
        $form['board_column[color]'] = '#ff0000';
        $form['board_column[wipLimit]'] = '3';
        $this->client->submit($form);

        self::assertResponseRedirects(\sprintf('/projects/%d/columns', $projectId));

        $columns = $this->columnsOf($projectId);
        self::assertCount($before + 1, $columns);
        $last = end($columns);
        self::assertSame('Bloqué', $last->getName());
        self::assertSame(3, $last->getWipLimit());
        self::assertSame($before, $last->getPosition(), 'La nouvelle colonne devrait être ajoutée en dernière position.');
    }

    public function testAdminCanMoveColumnDown(): void
    {
        $this->loginAs('admin@huttopia.com');
        $project = $this->projectByCode('HUT');
        $projectId = $project->getId();

        $columnsBefore = $this->columnsOf($projectId);
        $firstId = $columnsBefore[0]->getId();
        $secondId = $columnsBefore[1]->getId();

        $crawler = $this->client->request('GET', \sprintf('/projects/%d/columns', $projectId));
        $form = $crawler->filter('form[action$="/move/down"]')->first()->form();
        $this->client->submit($form);

        self::assertResponseRedirects();

        $repo = static::getContainer()->get(BoardColumnRepository::class);
        self::assertSame(1, $repo->find($firstId)->getPosition(), 'La 1re colonne devrait descendre en position 1.');
        self::assertSame(0, $repo->find($secondId)->getPosition(), 'La 2e colonne devrait remonter en position 0.');
    }

    public function testAdminCanDeleteColumn(): void
    {
        $this->loginAs('admin@huttopia.com');
        $project = $this->projectByCode('HUT');
        $projectId = $project->getId();

        $columnsBefore = $this->columnsOf($projectId);
        $before = \count($columnsBefore);
        $deletedId = $columnsBefore[0]->getId();

        $crawler = $this->client->request('GET', \sprintf('/projects/%d/columns', $projectId));
        $form = $crawler->filter('form[action$="/delete"]')->first()->form();
        $this->client->submit($form);

        self::assertResponseRedirects();

        $repo = static::getContainer()->get(BoardColumnRepository::class);
        self::assertNull($repo->find($deletedId), 'La colonne aurait dû être supprimée.');
        self::assertCount($before - 1, $this->columnsOf($projectId));
    }

    /** @return \App\Entity\BoardColumn[] */
    private function columnsOf(int $projectId): array
    {
        $project = static::getContainer()->get(\App\Repository\ProjectRepository::class)->find($projectId);

        return static::getContainer()->get(BoardColumnRepository::class)->findByProjectOrdered($project);
    }
}
