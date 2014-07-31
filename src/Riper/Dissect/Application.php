<?php
namespace Riper\Dissect;


use Riper\Dissect\Commands\CoverageMergeCommand;
use Riper\Dissect\Commands\ComposerSourceListCommand;
use Riper\Dissect\Commands\CoverageStatsCommand;
use Symfony\Component\Console\Application as AbstractApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * TextUI frontend for PHP_CodeCoverage.
 *
 * @author    Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright 2011-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link      http://github.com/sebastianbergmann/php-code-coverage/tree
 * @since     Class available since Release 2.0.0
 */
class Application extends AbstractApplication
{
    public function __construct($version='dev')
    {
        parent::__construct('Dissect', $version);
        $this->add(new CoverageMergeCommand());
        $this->add(new CoverageStatsCommand());
        $this->add(new ComposerSourceListCommand());
    }

    /**
     * Runs the current application.
     *
     * @param InputInterface  $input  An Input instance
     * @param OutputInterface $output An Output instance
     *
     * @return integer 0 if everything went fine, or an error code
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {

        parent::doRun($input, $output);
    }
}
