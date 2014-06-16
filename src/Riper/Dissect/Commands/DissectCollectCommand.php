<?php

namespace Riper\Dissect\Commands;

use Riper\Dissect\Factories\CodeCoverageFactory;
use Riper\Dissect\Factories\WriterFactory;
use Riper\Dissect\Services\CollectService;
use Riper\Dissect\Services\CoverageMergeService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DissectCollectCommand extends Command
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('collect')
            ->addArgument(
                'database',
                InputArgument::REQUIRED,
                'Location of the database to generate (SQLITE)'
            )
            ->addOption(
                'coverage',
                'c',
                InputOption::VALUE_REQUIRED,
                'Location of the coverage file to collect into the database'
            );
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return null|integer null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {


        $CollectService = new CollectService($input->getArgument('database'));

        $CollectService->setCoverageFile($input->getOption('coverage'));

        $CollectService->process();


        echo 'done' ."\n";


    }
}
