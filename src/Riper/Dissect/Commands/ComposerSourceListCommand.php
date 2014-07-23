<?php

namespace Riper\Dissect\Commands;

use Composer\Composer;
use Riper\Dissect\Factories\CodeCoverageFactory;
use Riper\Dissect\Factories\WriterFactory;
use Riper\Dissect\Services\ComposerSourcesService;
use Riper\Dissect\Services\CoverageMergeService;
use Riper\Dissect\Services\CoverageStatsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ComposerSourceListCommand extends Command
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('composer:sourceList')
            ->addArgument('composer.lock', InputArgument::REQUIRED, 'Path to the Composer.lock file')
            ->addOption('include-dev', null, InputOption::VALUE_NONE, 'Include source from "require-dev" packages')
            /*->addOption(
                'by-domains',
                null,
                InputOption::VALUE_NONE,
                'Group source by domain'
            )
            ->addOption(
                'by-vendor',
                null,
                InputOption::VALUE_NONE,
                'Group by vendor'
            )*/->setDescription('List all repository sources');
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

        if (!file_exists($input->getArgument('composer.lock')) || !is_readable($input->getArgument('composer.lock'))) {
            throw new \InvalidArgumentException(
                'The file "' . $input->getArgument('composer.lock') . '" does not exist or access is denied'
            );
        }


        $composerLockContent = file_get_contents($input->getArgument('composer.lock'));

        $serializer = \JMS\Serializer\SerializerBuilder::create()->build();


        $ComposerService = new ComposerSourcesService($composerLockContent, $serializer);

        $sources = $ComposerService->getSources($input->getOption('include-dev'));

        foreach ($sources as $source) {
            $name = str_pad($source['packageName'], 35);
            $output->writeln($name . ' : ' . $source['url']);
        }

    }
}
