<?php

/*
 * This file is part of Frontend Consistency Bundle
 *
 * (c) Wojciech Surmacz <wojciech.surmacz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Surmacz\FrontendConsistencyBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Surmacz\FrontendConsistencyBundle\Lib\Compare;
use Symfony\Component\Finder\Finder;

/**
 * Consistency Test Command
 * @author Wojciech Surmacz <wojciech.surmacz@gmail.com>
 */
class ConsistencyTestCommand extends FrontendConsistencyCommand
{
	/**
	 * @var bool
	 */
    protected $verbose = true;

    /**
     * @var bool
     */
    protected $init = false;

    /**
     * @var string
     */
    private static $filesToFindPattern = '*.php';

    /**
     * @var []
     */
    private $differenceFiles = [];

    /**
     * @var string
     */
    private $errorlogPath;

    /**
     * @var []
     */
    private $environments;

    /**
     * @var string
     */
    private $phpUnitPath;

    /**
     * @var string
     */
    private $phpUnitParams;

    /**
     * @var string
     */
    private $screenshotGlobalPath;

    /**
     * @var string
     */
    private $screenshotLocalPath;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * (non-PHPdoc)
     * @see \Surmacz\FrontendConsistencyBundle\Command\FrontendConsistencyCommand::initialize()
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->environments = $this
            ->getContainer()
            ->getParameter('frontend_consistency.environments');
        $this->phpUnitPath = $this
            ->getContainer()
            ->getParameter('frontend_consistency.php_unit_path');
        $this->phpUnitParams = $this
            ->getContainer()
            ->getParameter('frontend_consistency.php_unit_params');
        $this->screenshotGlobalPath = $this->topDir
            .'\\'
            .$this
                ->getContainer()
                ->getParameter('frontend_consistency.screenshot_global_path');
        $this->screenshotLocalPath = $this->topDir
            .'\\'
            .$this
                ->getContainer()
                ->getParameter('frontend_consistency.screenshot_local_path');
        $this->errorlogPath = $this->screenshotLocalPath.'\\error.log';
        $this->output = $output;
        $this->verbose = false === $input->getOption('no-verbose');
        $this->init = $input->getOption('init');
    }

    /**
     * @return []
     */
    protected function getConsistencyClasses()
    {
        $files = [];

        $finder = new Finder();
        $iterator = $finder
            ->files(self::$filesToFindPattern)
            ->path('/\/ConsistencyTests\//')
            ->in($this->topDir);
        foreach($iterator as $file) {
            $files[] = $file->getRealpath();
        }

        return $files;
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('consistency:verify')
            ->setDescription('Verification of frontend consistency')
            ->addOption(
               'no-verbose',
               null,
               InputOption::VALUE_NONE,
               'If set, only binary result will be returned (0 - fail, 1 - success).'
            )
            ->addOption(
               'init',
               null,
               InputOption::VALUE_NONE,
               'If set, initialization will be run.'
            );
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->console("Frontend Consistency Bundle 0.0.1 by Wojciech Surmacz");
        $this->console("");
        $this->console("Running selenium tests", false);
        chdir($this->topDir);
        foreach ($this->getConsistencyClasses() as $consistencyClass) {
            foreach($this->environments as $environmentName => $environmentParams) {
                $log = [];
                $phpunit = exec(
                    "{$this->phpUnitPath} {$this->phpUnitParams} {$consistencyClass} {$environmentName}",
                    $log,
                    $status
                );
                if (0 != $status) {
                    file_put_contents($this->errorlogPath, implode("\n", $log));
                    $this->console("");
                    $this->console("One of the tests has ended with an error ({$consistencyClass}). Check error.log file placed in local path.");
                    return;
                }
                $this->console('.', false);
            }
        }
        $this->console("Done");
        if ($this->init) {
            $this->console("Initialization", false);
            $copy = $this->getContainer()->get('frontend_consistency.copy');
            $copy->process($this->screenshotGlobalPath, $this->screenshotLocalPath);
            $this->console(".Done");
        }
        $this->console("Comparing generated screenshots", false);
        $compare = $this->getContainer()->get('frontend_consistency.compare');
        $this->differenceFiles = $compare->process($this->screenshotGlobalPath, $this->screenshotLocalPath);
        $this->prepareReport();

        return true;
    }

    /**
     * Preparing final report
     */
    private function prepareReport()
    {
        $this->console("");
        $this->console("--------------------------------------------");
        if (count($this->differenceFiles)) {
            $status = 0;
            $this->console("Found inconsistency(ies) in the following file(s):\n".implode("\n", $this->differenceFiles));
            $this->console("--------------------------------------------");
            $this->console("");
            $this->console("Tip: Verify differences of local and global screenshots and replace global screenshot with local one if local one is valid.");
        } else {
            $status = 1;
            $this->console("Inconsistencies not found - everything seems to be fine:)");
            $this->console("--------------------------------------------");
        };
        $this->console($status, true, true);
    }

    /**
     * Add text to the console
     * @param string $text
     * @param bool   $newLine
     * @param bool   $force
     */
    private function console($text, $newLine = true, $force = false)
    {
        if (!$this->verbose && !$force) {
            return;
        }
        ($newLine ? $this->output->writeln($text) : $this->output->write($text));
    }
}
