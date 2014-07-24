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
            )*/
            ->addOption('output-csv', null, InputOption::VALUE_REQUIRED, 'Generate a CSV file with all packages')
            ->setDescription('List all repository sources');
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

        /**
         * get sources :
         */
        $sources   = $ComposerService->getSources();
        $sourceDev = array(0);
        if ($input->getOption('include-dev')) {
            $sourceDev = $ComposerService->getDevSources();
        }


        /**
         * Display result in console
         */
        $output->writeln('<comment>Packages :</comment>');
        $output->writeln('');
        foreach ($sources as $source) {
            $name = str_pad($source['packageName'], 35);
            $output->writeln($name . ' : ' . $source['url']);
        }
        if ($input->getOption('include-dev')) {
            $output->writeln('');
            $output->writeln('<comment>Packages dev:</comment>');
            $output->writeln('');
            foreach ($sourceDev as $source) {
                $name = str_pad($source['packageName'], 35);
                $output->writeln($name . ' : ' . $source['url']);
            }
        }



        if ($input->getOption('output-csv')) {

            /**
             * Structure data for file output
             */
            array_walk(
                $sources,
                function (&$item) {
                    $item['type'] = 'require';
                }
            );
            array_walk(
                $sourceDev,
                function (&$item) {
                    $item['type'] = 'require-dev';
                }
            );
            $sources = array_merge($sources, $sourceDev);
            $keys    = array_keys($sources[0]);



            /**
             * output result in csv
             */
            $csv     = implode(',', $keys) . "\n";
            foreach ($sources as $source) {
                $csv .= implode(',',array_values($source)) . "\n";
            }
            $output->writeln('');
            $output->writeln('');
            $output->write('Writing "'.$input->getOption('output-csv').'"...');
            $csv = trim($csv);
            file_put_contents($input->getOption('output-csv'), $csv);
            $output->writeln('done');
        }

    }
}
