<?php

/*
 * This file is part of Frontend Consistency Bundle
 *
 * (c) Wojciech Surmacz <wojciech.surmacz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Surmacz\FrontendConsistencyBundle\Lib;

use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\DependencyInjection\Container;

/**
 * Abstract base for consistency selenium tests
 * @author Wojciech Surmacz <wojciech.surmacz@gmail.com>
 */
abstract class ConsistencyBase extends \PHPUnit_Extensions_SeleniumTestCase
{
	/**
	 * @var Kernel
	 */
    protected $kernel;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var string
     */
    protected $topDir;

    /**
     * @var []
     */
    protected $environments;

    /**
     * @var string
     */
    protected $browserPrefixUrl;

    /**
     * @var string
     */
    protected $screenshotLocalPath;

    /**
     * @var []
     */
    protected $environment = [];

    /**
     * @var string
     */
    protected $screenshotUrl = 'dummy';

    /**
     * @var string
     */
    protected $testClassName;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var bool
     */
    private $finalizeRun = false;

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_Assert::setUp()
     */
    public static function markTestSkipped($message = '')
    {
        throw new \Exception($message);
    }

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        require_once dirname(__DIR__).'/../../../app/AppKernel.php';
        $this->kernel = new \AppKernel('test', true);
        $this->kernel->boot();
        $this->container = $this->kernel->getContainer();
        $this->router = $this->container->get('router');
        $this->topDir = realpath($this->kernel->getRootDir().'/../');
        $this->environments = $this
            ->container
            ->getParameter('frontend_consistency.environments');
        $this->browserPrefixUrl = $this
            ->container
            ->getParameter('frontend_consistency.browser_prefix_url');
        $this->screenshotLocalPath = $this
            ->container
            ->getParameter('frontend_consistency.screenshot_local_path');

        $this->testClassName = get_called_class();
        $this->filesystem = new Filesystem();
        $this->initParams();
    }

    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown()
    {
        if (!$this->finalizeRun) {
            throw new \Exception('"finalize()" function must be run in each test');
        }
    }

    /**
     * Setting selenium environment
     * @throws ParameterNotFoundException
     */
    protected function setEnvironment()
    {
        global $argv, $argc;
        if (0 === $argc) {
            throw new ParameterNotFoundException('Environment parameter is obligatory');
        }

        $environment = end($argv);
        $environmentFound = false;
        foreach ($this->environments as $environmentName => $environmentParams) {
            if (strcmp($environment, $environmentName) === 0) {
                $this->setBrowser($environmentParams['browser']);
                $this->setHost($environmentParams['host']);
                $this->setPort($environmentParams['port']);
                $this->setTimeout($environmentParams['timeout']);
                $this->environment = [$environmentName => $environmentParams];
                $environmentFound = true;
                break;
            }
        }
        if (!$environmentFound) {
            throw new ParameterNotFoundException('Provided environment has not been found');
        }
    }

    /**
     * Initialize params
     */
    protected function initParams()
    {
        $this->setBrowserUrl($this->browserPrefixUrl);
        $this->setEnvironment();
        $this->screenshotPath = $this->topDir
            .'/'
            .$this->screenshotLocalPath
            .'/'
            .array_values($this->environment)[0]['path'];
    }

    /**
     * Finalize function should be run at least once in each test
     */
    protected function finalize()
    {
        $this->prepareScreenshotPath();
        $this->takeScreenshot();
        $this->finalizeRun = true;
    }

    /**
     * Prepare screenshot path
     * @throws \Exception
     */
    protected function prepareScreenshotPath()
    {
        if (!preg_match('/^(.+)\\\ConsistencyTests\\\(.+)$/', $this->testClassName, $classMatches)) {
            throw new \Exception("Wrong context class: {$class}");
        }
        if (!preg_match('/^test(.+)$/', $this->getName(), $functionMatches)) {
            throw new \Exception("Wrong context function: {$function}");
        }
        $topPath = str_replace('\\', '', $classMatches[1]);
        $lastPathArray = explode('\\', $classMatches[2]);
        $filename = array_pop($lastPathArray).$functionMatches[1];
        $directoriesArray = array_merge([$topPath], $lastPathArray);
        $directories = implode('/', $directoriesArray);
        $this->screenshotPath .= '/'.$directories;
        if (!is_dir($this->screenshotPath)) {
            $this->filesystem->mkdir($this->screenshotPath, 0777);
        }
        $this->testId = $filename;
    }
}
