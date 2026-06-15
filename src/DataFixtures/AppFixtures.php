<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\BoardColumn;
use App\Entity\Project;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\Priority;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // --- Utilisateurs -------------------------------------------------
        $admin = $this->makeUser('admin@huttopia.com', 'Alice Admin', 'admin', [User::ROLE_ADMIN]);
        $marie = $this->makeUser('marie@huttopia.com', 'Marie Martin', 'marie');
        $paul = $this->makeUser('paul@huttopia.com', 'Paul Durand', 'paul');

        foreach ([$admin, $marie, $paul] as $user) {
            $manager->persist($user);
        }

        // --- Projet de démonstration -------------------------------------
        $project = (new Project())
            ->setName('Refonte du site vitrine')
            ->setCode('HUT')
            ->setDescription('Modernisation complète du site vitrine : design, performances et accessibilité.')
            ->setOwner($admin);

        $columns = [];
        foreach ([
            ['À faire', '#64748b', null],
            ['En cours', '#2563eb', 5],
            ['En revue', '#a855f7', null],
            ['Terminé', '#16a34a', null],
        ] as $position => [$name, $color, $wip]) {
            $column = (new BoardColumn())
                ->setName($name)
                ->setColor($color)
                ->setPosition($position)
                ->setWipLimit($wip);
            $project->addColumn($column);
            $columns[$name] = $column;
        }
        $manager->persist($project);

        $this->createTickets($manager, $columns, [
            ['À faire', 'Maquetter la page d’accueil', Priority::High, $marie],
            ['À faire', 'Rédiger le cahier des charges SEO', Priority::Medium, $paul],
            ['À faire', 'Choisir une police d’écriture', Priority::Low, null],
            ['En cours', 'Intégrer le menu responsive', Priority::High, $marie],
            ['En cours', 'Mettre en place le formulaire de contact', Priority::Medium, $paul],
            ['En revue', 'Optimiser les images (WebP)', Priority::Medium, $marie],
            ['Terminé', 'Configurer l’environnement de dev', Priority::Urgent, $admin],
            ['Terminé', 'Choisir l’hébergeur', Priority::Low, $admin],
        ]);

        // --- Second projet -----------------------------------------------
        $mobile = (new Project())
            ->setName('Application mobile')
            ->setCode('MOB')
            ->setDescription('Application mobile de réservation pour les campings.')
            ->setOwner($admin);

        $mobileColumns = [];
        foreach ([
            ['Backlog', '#64748b', null],
            ['Sprint en cours', '#2563eb', 4],
            ['Terminé', '#16a34a', null],
        ] as $position => [$name, $color, $wip]) {
            $column = (new BoardColumn())
                ->setName($name)
                ->setColor($color)
                ->setPosition($position)
                ->setWipLimit($wip);
            $mobile->addColumn($column);
            $mobileColumns[$name] = $column;
        }
        $manager->persist($mobile);

        $this->createTickets($manager, $mobileColumns, [
            ['Backlog', 'Authentification par e-mail', Priority::High, $paul],
            ['Backlog', 'Écran de recherche de campings', Priority::Medium, $marie],
            ['Sprint en cours', 'Paiement in-app', Priority::Urgent, $paul],
            ['Terminé', 'Initialisation du projet', Priority::Low, $admin],
        ]);

        $manager->flush();
    }

    /**
     * @param list<string> $roles
     */
    private function makeUser(string $email, string $fullName, string $plainPassword, array $roles = []): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setFullName($fullName)
            ->setRoles($roles);
        $user->setPassword($this->hasher->hashPassword($user, $plainPassword));

        return $user;
    }

    /**
     * @param array<string, BoardColumn>                              $columns
     * @param array<int, array{0:string,1:string,2:Priority,3:?User}> $tickets
     */
    private function createTickets(ObjectManager $manager, array $columns, array $tickets): void
    {
        $positions = [];
        foreach ($tickets as [$columnName, $title, $priority, $assignee]) {
            $column = $columns[$columnName];
            $position = $positions[$columnName] ?? 0;
            $positions[$columnName] = $position + 1;

            $ticket = (new Ticket())
                ->setTitle($title)
                ->setPriority($priority)
                ->setAssignee($assignee)
                ->setReporter($column->getProject()->getOwner())
                ->setPosition($position);
            $column->addTicket($ticket);
            $manager->persist($ticket);
        }
    }
}
