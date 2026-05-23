<?php

namespace App\Command;

use App\Entity\LogInUsers; // Use LogInUsers based on your security.yaml
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Creates a new user account.',
    aliases: ['app:add-user']
)]
class CreateUserCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'The email address of the new user.')
            ->addArgument('password', InputArgument::REQUIRED, 'The plaintext password for the new user.');
        // Optionally create a staff user instead of a client
        $this->addOption('staff', null, InputOption::VALUE_NONE, 'Create the user with ROLE_STAFF');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('StayGrid User Creation');

        $email = $input->getArgument('email');
        $plaintextPassword = $input->getArgument('password');

        // 1. Check if user already exists
        if ($this->entityManager->getRepository(LogInUsers::class)->findOneBy(['email' => $email])) {
            $io->error(sprintf('User with email "%s" already exists.', $email));
            return Command::FAILURE;
        }

        // 2. Create the user entity
        $user = new LogInUsers();
        $user->setEmail($email);
        // Set default role: ROLE_CLIENT, or ROLE_STAFF when --staff passed
        $roles = $input->getOption('staff') ? [LogInUsers::ROLE_STAFF] : [LogInUsers::ROLE_CLIENT];
        $user->setRoles($roles);

        // 3. Hash the password using the configured hasher
        // This is the part that failed before we fixed security.yaml
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $plaintextPassword
        );
        $user->setPassword($hashedPassword);

        // 4. Persist and flush
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Successfully created new user: %s', $email));

        return Command::SUCCESS;
    }
}