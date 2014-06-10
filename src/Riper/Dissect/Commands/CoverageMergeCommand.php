<?php

namespace Riper\Dissect\Commands;

use Ilius\Component\CoverageReportMerge\CodeCoverageFactory;
use Ilius\Component\CoverageReportMerge\CoverageReportMergeService;
use Ilius\Component\CoverageReportMerge\WriterFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CoverageReportMergeCommand extends Command
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('coverage')
            ->addArgument(
                'directory',
                InputArgument::REQUIRED,
                'Directory where Report will be generated'
            )
            ->addOption(
                'coverageDirectory',
                'd',
                InputOption::VALUE_REQUIRED,
                'folder where coverage files are located (*.cov)'
            )->addOption(
                'lowUpperBound',
                'l',
                InputOption::VALUE_REQUIRED,
                'Level of coverage below witch code is considered not enough covered (red)'
            )->addOption(
                'highLowerBound',
                'i',
                InputOption::VALUE_REQUIRED,
                'Level of coverage to consider code well covered (green)'
            )
            ->addOption(
                'files',
                'f',
                InputOption::VALUE_REQUIRED,
                'List of coverage file separated by comma'
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


        $CoverageReportMerge = new CoverageReportMergeService(new WriterFactory(), new CodeCoverageFactory());


        if ($input->getOption('coverageDirectory')) {
            $CoverageReportMerge->findCoverageReport($input->getOption('coverageDirectory'));
        } else {
            if ($input->getOption('coverageDirectory')) {
                $reports = explode(',', $input->getOption('coverageDirectory'));
                foreach ($reports as $report) {
                    $CoverageReportMerge->addCoverageReport($report);
                }
            }
        }

        $CoverageReportMerge->setOutputHTMLReportFolder($input->getArgument('directory'));

        $low  = $input->getOption('lowUpperBound');
        $high = $input->getOption('highLowerBound');

        if ($low !== null) {
            $CoverageReportMerge->setLowUpperBound($low);
        }

        if ($high !== null) {
            $CoverageReportMerge->setHighLowerBound($high);
        }

        if (class_exists('PHPUnit_Runner_Version')) {
            $CoverageReportMerge->setGeneratorName(
                sprintf(
                    ' and <a href="http://phpunit.de/">PHPUnit %s</a>',
                    \PHPUnit_Runner_Version::id()
                )
            );
        } else {
            $CoverageReportMerge->setGeneratorName('CoverageReportMerge with unknown version of PHPUnit');
        }

        $output->write('Begening merge & generation');
        $CoverageReportMerge->generate();
        $output->writeln('... done');
    }
}
