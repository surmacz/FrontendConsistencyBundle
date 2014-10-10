<?php

/*
 * This file is part of Frontend Consistency Bundle
 *
 * (c) Wojciech Surmacz <wojciech.surmacz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Surmacz\FrontendConsistencyBundle\Services;

use Symfony\Component\Finder\Finder;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Abstract base for services
 * @author Wojciech Surmacz <wojciech.surmacz@gmail.com>
 */
abstract class Base
{
	/**
	 * @var string
	 */
    protected static $filesToFindPattern = '*.png';

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->filesystem = new Filesystem();
    }

    /**
     * @param string $globalPath
     * @param string $localPath
     */
    abstract public function process($globalPath, $localPath);

    /**
     * @param string $path
     * @return []
     */
    protected function getFiles($path)
    {
        $finder = new Finder();
        $files = [];
        $iterator = $finder
            ->files()
            ->name(self::$filesToFindPattern)
            ->in($path);
        foreach($iterator as $file) {
            $files[] = $file->getRealpath();
        }

        return $files;
    }

    /**
     * @param []     $files
     * @param string $prefix
     * @return []
     */
    protected function cleanPrefix($files, $prefix)
    {
        array_walk($files, function (&$element) use ($prefix) {
            $element = str_replace($prefix, '', $element);
        });

        return $files;
    }

    /**
     * @param string $command
     * @throws \RuntimeException
     * @return \Symfony\Component\Process\Process
     */
    protected function processFactory($command)
    {
        $process = new Process($command);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return $process;
    }
}
