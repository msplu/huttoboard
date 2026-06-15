<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\BoardColumn;
use App\Entity\Project;
use App\Form\BoardColumnType;
use App\Repository\BoardColumnRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class BoardColumnController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BoardColumnRepository $columns,
    ) {
    }

    #[Route('/projects/{id}/columns', name: 'app_column_index', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function index(Request $request, Project $project): Response
    {
        $column = new BoardColumn();
        $form = $this->createForm(BoardColumnType::class, $column, [
            'action' => $this->generateUrl('app_column_index', ['id' => $project->getId()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $column->setPosition($project->getNextColumnPosition());
            $project->addColumn($column);
            $this->columns->save($column);
            $this->addFlash('success', \sprintf('Colonne « %s » ajoutée.', $column->getName()));

            return $this->redirectToRoute('app_column_index', ['id' => $project->getId()]);
        }

        return $this->render('column/index.html.twig', [
            'project' => $project,
            'columns' => $project->getColumns(),
            'form' => $form,
        ]);
    }

    #[Route('/columns/{id}/edit', name: 'app_column_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, BoardColumn $column): Response
    {
        $form = $this->createForm(BoardColumnType::class, $column);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->columns->save($column);
            $this->addFlash('success', 'Colonne mise à jour.');

            return $this->redirectToRoute('app_column_index', ['id' => $column->getProject()->getId()]);
        }

        return $this->render('column/edit.html.twig', [
            'project' => $column->getProject(),
            'column' => $column,
            'form' => $form,
        ]);
    }

    #[Route('/columns/{id}/delete', name: 'app_column_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, BoardColumn $column): Response
    {
        $project = $column->getProject();

        if ($this->isCsrfTokenValid('delete_column_'.$column->getId(), (string) $request->request->get('_token'))) {
            $this->columns->remove($column);
            $this->normalizePositions($project);
            $this->addFlash('success', 'Colonne supprimée (et ses tickets).');
        }

        return $this->redirectToRoute('app_column_index', ['id' => $project->getId()]);
    }

    #[Route('/columns/{id}/move/{direction}', name: 'app_column_move', methods: ['POST'], requirements: ['id' => '\d+', 'direction' => 'up|down'])]
    public function move(Request $request, BoardColumn $column, string $direction): Response
    {
        $project = $column->getProject();

        if (!$this->isCsrfTokenValid('move_column_'.$column->getId(), (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('app_column_index', ['id' => $project->getId()]);
        }

        $ordered = $this->columns->findByProjectOrdered($project);
        $index = array_search($column, $ordered, true);
        $swapWith = 'up' === $direction ? $index - 1 : $index + 1;

        if (false !== $index && isset($ordered[$swapWith])) {
            $other = $ordered[$swapWith];
            $tmp = $column->getPosition();
            $column->setPosition($other->getPosition());
            $other->setPosition($tmp);
            $this->em->flush();
        }

        return $this->redirectToRoute('app_column_index', ['id' => $project->getId()]);
    }

    /** Réindexe les positions des colonnes de 0..n après une suppression. */
    private function normalizePositions(Project $project): void
    {
        $position = 0;
        foreach ($this->columns->findByProjectOrdered($project) as $column) {
            $column->setPosition($position++);
        }
        $this->em->flush();
    }
}
