<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\BoardColumn;
use App\Entity\Project;
use App\Entity\Ticket;
use App\Form\TicketType;
use App\Repository\BoardColumnRepository;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TicketController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TicketRepository $tickets,
        private readonly BoardColumnRepository $columns,
    ) {
    }

    #[Route('/projects/{id}/tickets/new', name: 'app_ticket_new', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function new(Request $request, Project $project): Response
    {
        $ticket = new Ticket();

        // Pré-sélection éventuelle de la colonne via ?column=ID.
        $columnId = $request->query->getInt('column');
        if ($columnId > 0) {
            $preselected = $this->columns->find($columnId);
            if ($preselected && $preselected->getProject() === $project) {
                $ticket->setColumn($preselected);
            }
        }

        $form = $this->createForm(TicketType::class, $ticket, ['project' => $project]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ticket->setReporter($this->getUser());
            $ticket->setPosition($this->tickets->maxPositionInColumn($ticket->getColumn()) + 1);
            $this->tickets->save($ticket);
            $this->addFlash('success', \sprintf('Ticket « %s » créé.', $ticket->getReference()));

            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        return $this->render('ticket/new.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/tickets/{id}', name: 'app_ticket_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Ticket $ticket): Response
    {
        return $this->render('ticket/show.html.twig', [
            'ticket' => $ticket,
        ]);
    }

    #[Route('/tickets/{id}/edit', name: 'app_ticket_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Ticket $ticket): Response
    {
        $project = $ticket->getProject();
        $form = $this->createForm(TicketType::class, $ticket, ['project' => $project]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->tickets->save($ticket);
            $this->addFlash('success', 'Ticket mis à jour.');

            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        return $this->render('ticket/edit.html.twig', [
            'project' => $project,
            'ticket' => $ticket,
            'form' => $form,
        ]);
    }

    #[Route('/tickets/{id}/delete', name: 'app_ticket_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Ticket $ticket): Response
    {
        $project = $ticket->getProject();

        if ($this->isCsrfTokenValid('delete_ticket_'.$ticket->getId(), (string) $request->request->get('_token'))) {
            $this->tickets->remove($ticket);
            $this->addFlash('success', 'Ticket supprimé.');
        }

        return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
    }

    /**
     * Endpoint AJAX appelé par le drag & drop : déplace un ticket et
     * recalcule l'ordre de la colonne cible.
     */
    #[Route('/board/move', name: 'app_board_move', methods: ['POST'])]
    public function move(Request $request): JsonResponse
    {
        $payload = $request->toArray();
        $token = (string) ($payload['_token'] ?? '');

        if (!$this->isCsrfTokenValid('board_move', $token)) {
            return $this->json(['error' => 'Jeton CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        $ticketId = (int) ($payload['ticketId'] ?? 0);
        $toColumnId = (int) ($payload['toColumnId'] ?? 0);
        /** @var int[] $orderedIds */
        $orderedIds = array_map('intval', (array) ($payload['orderedIds'] ?? []));

        $ticket = $this->tickets->find($ticketId);
        $targetColumn = $this->columns->find($toColumnId);

        if (!$ticket instanceof Ticket || !$targetColumn instanceof BoardColumn) {
            return $this->json(['error' => 'Ticket ou colonne introuvable.'], Response::HTTP_NOT_FOUND);
        }

        // Sécurité : on interdit le transfert d'un ticket vers un autre projet.
        if ($ticket->getProject() !== $targetColumn->getProject()) {
            return $this->json(['error' => 'Déplacement inter-projets interdit.'], Response::HTTP_BAD_REQUEST);
        }

        $ticket->setColumn($targetColumn);

        // Réordonne la colonne cible d'après la liste fournie par le client.
        $position = 0;
        foreach ($orderedIds as $id) {
            $t = $this->tickets->find($id);
            if ($t instanceof Ticket && $t->getColumn() === $targetColumn) {
                $t->setPosition($position++);
            }
        }

        $this->em->flush();

        return $this->json([
            'status' => 'ok',
            'ticket' => $ticket->getId(),
            'column' => $targetColumn->getId(),
        ]);
    }
}
