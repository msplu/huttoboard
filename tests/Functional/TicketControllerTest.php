<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Repository\BoardColumnRepository;
use App\Repository\TicketRepository;

class TicketControllerTest extends AbstractWebTestCase
{
    public function testRegularUserCanCreateTicket(): void
    {
        $this->loginAs('marie@huttopia.com');
        $project = $this->projectByCode('HUT');
        $column = $this->firstColumn($project);
        $columnId = $column->getId();

        $crawler = $this->client->request('GET', \sprintf('/projects/%d/tickets/new?column=%d', $project->getId(), $columnId));
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Créer le ticket')->form();
        $form['ticket[title]'] = 'Ticket créé par un test';
        $form['ticket[description]'] = 'Description de test';
        $form['ticket[priority]'] = 'high';
        $form['ticket[column]'] = (string) $columnId;
        $this->client->submit($form);

        self::assertResponseRedirects();

        $ticket = static::getContainer()->get(TicketRepository::class)->findOneBy(['title' => 'Ticket créé par un test']);
        self::assertNotNull($ticket, 'Le ticket n’a pas été créé.');
        self::assertSame($columnId, $ticket->getColumn()->getId());
        self::assertSame('marie@huttopia.com', $ticket->getReporter()->getEmail());
    }

    public function testBoardMovePersistsNewColumnAndOrder(): void
    {
        $this->loginAs('marie@huttopia.com');
        $project = $this->projectByCode('HUT');

        $columns = static::getContainer()->get(BoardColumnRepository::class)->findByProjectOrdered($project);
        $sourceColumn = $columns[0];
        $targetColumn = $columns[1];
        $ticket = $sourceColumn->getTickets()->first();
        self::assertNotFalse($ticket, 'La première colonne devrait contenir au moins un ticket.');

        $ticketId = $ticket->getId();
        $targetId = $targetColumn->getId();

        // Récupère le jeton CSRF rendu dans le tableau.
        $crawler = $this->client->request('GET', \sprintf('/projects/%d', $project->getId()));
        $token = $crawler->filter('.board')->attr('data-board-csrf-value');

        $this->client->request(
            'POST',
            '/board/move',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                '_token' => $token,
                'ticketId' => $ticketId,
                'toColumnId' => $targetId,
                'orderedIds' => [$ticketId],
            ], \JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();
        self::assertJson($this->client->getResponse()->getContent());

        $moved = static::getContainer()->get(TicketRepository::class)->find($ticketId);
        self::assertSame($targetId, $moved->getColumn()->getId(), 'Le ticket aurait dû changer de colonne.');
        self::assertSame(0, $moved->getPosition(), 'Le ticket aurait dû être réordonné en première position.');
    }

    public function testBoardMoveRejectsInvalidCsrf(): void
    {
        $this->loginAs('marie@huttopia.com');
        $project = $this->projectByCode('HUT');
        $columns = static::getContainer()->get(BoardColumnRepository::class)->findByProjectOrdered($project);
        $ticket = $columns[0]->getTickets()->first();

        $this->client->request(
            'POST',
            '/board/move',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                '_token' => 'jeton-invalide',
                'ticketId' => $ticket->getId(),
                'toColumnId' => $columns[1]->getId(),
                'orderedIds' => [$ticket->getId()],
            ], \JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(403);
    }

    public function testBoardMoveRejectsCrossProject(): void
    {
        $this->loginAs('marie@huttopia.com');
        $hut = $this->projectByCode('HUT');
        $mob = $this->projectByCode('MOB');

        $columnRepo = static::getContainer()->get(BoardColumnRepository::class);
        $hutColumns = $columnRepo->findByProjectOrdered($hut);
        $mobColumns = $columnRepo->findByProjectOrdered($mob);
        $ticket = $hutColumns[0]->getTickets()->first();

        $crawler = $this->client->request('GET', \sprintf('/projects/%d', $hut->getId()));
        $token = $crawler->filter('.board')->attr('data-board-csrf-value');

        // On tente de déplacer un ticket de HUT vers une colonne de MOB.
        $this->client->request(
            'POST',
            '/board/move',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                '_token' => $token,
                'ticketId' => $ticket->getId(),
                'toColumnId' => $mobColumns[0]->getId(),
                'orderedIds' => [$ticket->getId()],
            ], \JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(400);
    }

    public function testTicketShowPageDisplaysReference(): void
    {
        $this->loginAs('marie@huttopia.com');
        $project = $this->projectByCode('HUT');
        $ticket = $this->firstColumn($project)->getTickets()->first();

        $this->client->request('GET', \sprintf('/tickets/%d', $ticket->getId()));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.ref', 'HUT-');
    }
}
