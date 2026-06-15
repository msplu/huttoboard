<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\BoardColumn;
use App\Entity\Project;
use App\Form\ProjectType;
use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/projects')]
final class ProjectController extends AbstractController
{
    /** Colonnes créées par défaut à l'ouverture d'un nouveau projet. */
    private const DEFAULT_COLUMNS = [
        ['name' => 'À faire', 'color' => '#64748b'],
        ['name' => 'En cours', 'color' => '#2563eb'],
        ['name' => 'Terminé', 'color' => '#16a34a'],
    ];

    #[Route('', name: 'app_project_index', methods: ['GET'])]
    public function index(ProjectRepository $projects): Response
    {
        return $this->render('project/index.html.twig', [
            'projects' => $projects->findAllOrdered(),
        ]);
    }

    #[Route('/new', name: 'app_project_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, ProjectRepository $projects): Response
    {
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $project->setOwner($this->getUser());

            // Tableau prêt à l'emploi : trois colonnes par défaut.
            foreach (self::DEFAULT_COLUMNS as $position => $definition) {
                $column = (new BoardColumn())
                    ->setName($definition['name'])
                    ->setColor($definition['color'])
                    ->setPosition($position);
                $project->addColumn($column);
            }

            $projects->save($project);
            $this->addFlash('success', \sprintf('Projet « %s » créé.', $project->getName()));

            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        return $this->render('project/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_project_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Project $project): Response
    {
        return $this->render('project/show.html.twig', [
            'project' => $project,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_project_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Project $project, ProjectRepository $projects): Response
    {
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $projects->save($project);
            $this->addFlash('success', 'Projet mis à jour.');

            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        return $this->render('project/edit.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_project_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Project $project, ProjectRepository $projects): Response
    {
        if ($this->isCsrfTokenValid('delete_project_'.$project->getId(), (string) $request->request->get('_token'))) {
            $projects->remove($project);
            $this->addFlash('success', 'Projet supprimé.');
        }

        return $this->redirectToRoute('app_project_index');
    }
}
