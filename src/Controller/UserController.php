<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    #[Route('', name: 'app_user_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $this->users->findAllOrdered(),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->applyRole($user, (string) $form->get('role')->getData());
            $user->setPassword($this->hasher->hashPassword($user, (string) $form->get('plainPassword')->getData()));
            $this->users->save($user);
            $this->addFlash('success', \sprintf('Utilisateur « %s » créé.', $user->getFullName()));

            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => true,
            'current_role' => $user->isAdmin() ? User::ROLE_ADMIN : User::ROLE_USER,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->applyRole($user, (string) $form->get('role')->getData());

            $plainPassword = (string) $form->get('plainPassword')->getData();
            if ('' !== $plainPassword) {
                $user->setPassword($this->hasher->hashPassword($user, $plainPassword));
            }

            $this->users->save($user);
            $this->addFlash('success', 'Utilisateur mis à jour.');

            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_user_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, User $user): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');

            return $this->redirectToRoute('app_user_index');
        }

        if ($this->isCsrfTokenValid('delete_user_'.$user->getId(), (string) $request->request->get('_token'))) {
            $this->users->remove($user);
            $this->addFlash('success', 'Utilisateur supprimé.');
        }

        return $this->redirectToRoute('app_user_index');
    }

    private function applyRole(User $user, string $role): void
    {
        // ROLE_USER est implicite (cf. User::getRoles()).
        $user->setRoles(User::ROLE_ADMIN === $role ? [User::ROLE_ADMIN] : []);
    }
}
