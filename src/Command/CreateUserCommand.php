<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Crée un utilisateur (option --admin pour un administrateur).',
)]
final class CreateUserCommand extends Command
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Adresse e-mail')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Nom complet')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Mot de passe')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Crée un administrateur');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getOption('email') ?? $io->ask('Adresse e-mail');
        $name = $input->getOption('name') ?? $io->ask('Nom complet');
        $password = $input->getOption('password') ?? $io->askHidden('Mot de passe');
        $isAdmin = (bool) $input->getOption('admin');

        if (!$email || !$name || !$password) {
            $io->error('E-mail, nom et mot de passe sont requis.');

            return Command::INVALID;
        }

        if ($this->users->findOneBy(['email' => $email])) {
            $io->error(\sprintf('Un compte existe déjà avec l’e-mail « %s ».', $email));

            return Command::FAILURE;
        }

        $user = (new User())
            ->setEmail($email)
            ->setFullName($name)
            ->setRoles($isAdmin ? [User::ROLE_ADMIN] : []);
        $user->setPassword($this->hasher->hashPassword($user, $password));

        $this->users->save($user);

        $io->success(\sprintf('Utilisateur « %s » créé%s.', $email, $isAdmin ? ' (administrateur)' : ''));

        return Command::SUCCESS;
    }
}
