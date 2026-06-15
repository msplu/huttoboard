<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\BoardColumn;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\BoardColumnRepository;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    protected function user(string $email): User
    {
        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
        self::assertNotNull($user, \sprintf('Utilisateur de test « %s » introuvable.', $email));

        return $user;
    }

    /** Connecte un utilisateur sans passer par le formulaire (firewall court-circuité). */
    protected function loginAs(string $email): User
    {
        $user = $this->user($email);
        $this->client->loginUser($user);

        return $user;
    }

    protected function projectByCode(string $code): Project
    {
        $project = static::getContainer()->get(ProjectRepository::class)->findOneBy(['code' => $code]);
        self::assertNotNull($project, \sprintf('Projet de test « %s » introuvable.', $code));

        return $project;
    }

    protected function firstColumn(Project $project): BoardColumn
    {
        $columns = static::getContainer()->get(BoardColumnRepository::class)->findByProjectOrdered($project);
        self::assertNotEmpty($columns, 'Le projet de test n’a pas de colonne.');

        return $columns[0];
    }
}
