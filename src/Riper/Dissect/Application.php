<?php
namespace Riper\Dissect;


use Riper\Dissect\Commands\CoverageMergeCommand;
use Riper\Dissect\Commands\DissectCollectCommand;
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
    public function __construct()
    {
        parent::__construct('Dissect', 'dev');
        $this->add(new CoverageMergeCommand());
        $this->add(new DissectCollectCommand());
    }

    /**
     * Runs the current application.
     *
     * @param InputInterface $input An Input instance
     * @param OutputInterface $output An Output instance
     *
     * @return integer 0 if everything went fine, or an error code
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        if (!$input->hasParameterOption('--quiet')) {
            $output->write(
                sprintf(
                    "Dissect %s by Riper.\n\n",
                    $this->getVersion()
                )
            );
        }

        if ($input->hasParameterOption('--version') ||
            $input->hasParameterOption('-V')
        ) {
            exit;
        }

        parent::doRun($input, $output);
    }
}
