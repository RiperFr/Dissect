<?php

namespace Riper\Dissect\Commands;

use Riper\Dissect\Factories\CodeCoverageFactory;
use Riper\Dissect\Factories\WriterFactory;
use Riper\Dissect\Services\CoverageMergeService;
use Riper\Dissect\Services\CoverageStatsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CoverageStatsCommand extends Command
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('coverage:stats')
            ->addOption(
                'coverageFile',
                'c',
                InputOption::VALUE_REQUIRED,
                'File that contain coverage data (*.xml)'
            )->addOption(
                'reportCSV',
                'e',
                InputOption::VALUE_REQUIRED,
                'File path of the CSV report to generate'
            )->addOption(
                'metric',
                'm',
                InputOption::VALUE_REQUIRED,
                'Define which metric to aggregate. available metric are :
    - methods
    - coveredMethods
    - coveredMethodsPercent
    - statements
    - coveredStatements
    - coveredStatementsPercent
    - elements
    - coveredElements
    - coveredElementsPercent'
            )->addOption(
                'groupBy',
                'g',
                InputOption::VALUE_REQUIRED,
                'GroupByParam'
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

        $service = new CoverageStatsService();
        $service->setCSVReportFiles($input->getOption('reportCSV'));
        $coverageFiles = $input->getOption('coverageFile');
        $coverageFiles = explode(',', $coverageFiles);
        foreach ($coverageFiles as $coverageFile) {
            $service->setCoverageFile($coverageFile);
        }

        $groupBy = $input->getOption('groupBy');
        if ($groupBy == null) {
            throw new \InvalidArgumentException('The parameter GroupBy si mandatory');
        }

        $metric = $input->getOption('metric');
        if ($metric !== null) {
            $service->setReportedMetric($metric);
        }

        $groupBy    = explode(',', $groupBy, 3);
        $groupBy[2] = explode(',', $groupBy[2]);

        $service->setGroupBy($groupBy[0], $groupBy[1], $groupBy[2]);


        $stats = $service->generate();
        $csv   = "";
        $csv .= implode(',', array_keys($stats)) . "\n";
        $csv .= implode(',', array_values($stats)) . "\n";

        $output->writeln(print_r($stats, 1));

        file_put_contents($input->getOption('reportCSV'), $csv);

        echo 'done' . "\n";
    }
}
