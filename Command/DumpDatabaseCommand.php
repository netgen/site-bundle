<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Command;

use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class DumpDatabaseCommand extends ContainerAwareCommand
{
    protected function configure(): void
    {
        $this->setName('ngmore:database:dump')
            ->setDescription('Dumps the currently configured database to the provided file')
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'File name where to write the database dump'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $container = $this->getContainer();

        $databaseName = $container->getParameter('database_name');
        $databaseHost = $container->getParameter('database_host');
        $databaseUser = $container->getParameter('database_user');
        $databasePassword = $container->getParameter('database_password');

        $filePath = getcwd() . DIRECTORY_SEPARATOR . trim($input->getArgument('file'), '/');
        $targetDirectory = dirname($filePath);
        $fileName = basename($filePath);

        $fs = new Filesystem();
        if (!$fs->exists($targetDirectory)) {
            $fs->mkdir($targetDirectory);
        }

        $process = new Process(
            [
                'mysqldump',
                '-u',
                $databaseUser,
                '-h',
                $databaseHost,
				'--opt',
                '--quick',
                '--single-transaction',
                '-r',
                $targetDirectory . '/' . $fileName,
                $databaseName,
            ],
            null,
            [
                'MYSQL_PWD' => $databasePassword,
            ],
            null,
            null
        );

        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException($process->getErrorOutput());
        }

        $output->writeln('<info>Database dump complete.</info>');

        return 0;
    }
}
