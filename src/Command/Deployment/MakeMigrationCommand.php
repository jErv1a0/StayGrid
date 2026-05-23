<?php

namespace App\Command\Deployment;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'make:migration',
    description: 'Production deployment shim for Railway migration hooks.'
)]
class MakeMigrationCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->warning('make:migration is not used in production. Database migrations are handled by the container startup script.');

        return Command::SUCCESS;
    }
}