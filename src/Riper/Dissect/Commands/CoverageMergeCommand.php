<?php

namespace Riper\Dissect\Commands;

use Riper\Dissect\Factories\CodeCoverageFactory;
use Riper\Dissect\Factories\WriterFactory;
use Riper\Dissect\Services\CoverageMergeService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CoverageMergeCommand extends Command
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('coverage:merge')
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
            )->addOption(
                'xmlFileOutput',
                'x',
                InputOption::VALUE_REQUIRED,
                'The path to the xml file to generate (default: coverage.xml)'
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


        $CoverageReportMerge = new CoverageMergeService(new WriterFactory(), new CodeCoverageFactory());


        if ($input->getOption('coverageDirectory')) {
            $CoverageReportMerge->findCoverageReport($input->getOption('coverageDirectory'));
        } else {
            if ($input->getOption('files')) {
                $reports = explode(',', $input->getOption('files'));
                foreach ($reports as $report) {
                    $CoverageReportMerge->addCoverageReport($report);
                }
            }
        }

        $CoverageReportMerge->setOutputHTMLReportFolder($input->getArgument('directory'));

        $low  = $input->getOption('lowUpperBound');
        $high = $input->getOption('highLowerBound');
        $coverageXML = $input->getOption('xmlFileOutput');

        if($coverageXML !== null){
            $CoverageReportMerge->setOutputXMLReportFile($input->getOption('xmlFileOutput'));
        }

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

        $output->write('Beginning merge & generation : ');
        $CoverageReportMerge->generate();
        $output->writeln('... done');
    }
}
