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

/**
 * Comparing screenshots service
 * @author Wojciech Surmacz <wojciech.surmacz@gmail.com>
 */
class Compare extends Base
{
	/**
	 * @var string
	 */
    private $dummyFilename = 'dummy_error';

    /**
     * @var string
     */
    private $compareTool;

    /**
     * @var string
     */
    private $identifyTool;

    /**
     * @var string
     */
    private $globalPath;

    /**
     * @var string
     */
    private $localPath;

    /**
     * @var []
     */
    private $paired = [];

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->compareTool = $this->container->getParameter('frontend_consistency.image_compare_path');
        $this->identifyTool = $this->container->getParameter('frontend_consistency.image_identify_path');
    }

    /**
     * (non-PHPdoc)
     * @see \Surmacz\FrontendConsistencyBundle\Services\Base::process()
     */
    public function process($globalPath, $localPath)
    {
        $this->globalPath = $globalPath;
        $this->localPath = $localPath;
        $globalsWithoutPrefix = $this->cleanPrefix($this->getGlobalFiles(), $this->globalPath);
        $localsWithoutPrefix = $this->cleanPrefix($this->getLocalFiles(), $this->localPath);
        $this->paired = $this->pair($globalsWithoutPrefix, $localsWithoutPrefix);

        return $this->compare();
    }

    /**
     * @return []
     */
    private function getGlobalFiles()
    {
        return $this->getFiles($this->globalPath);
    }

    /**
     * @return []
     */
    private function getLocalFiles()
    {
        return $this->getFiles($this->localPath);
    }

    /**
     * Pair global and local screenshots
     * @param [] $globals
     * @param [] $locals
     * @return []
     */
    private function pair($globals, $locals)
    {
        $counter = [];
        foreach(array_merge($globals, $locals) as $path) {
            (isset($counter[$path]) ? $counter[$path]++ : $counter[$path] = 1);
        }
        $paired = array_keys(array_filter($counter, function ($element) {
            return 2 == $element;
        }));

        return $paired;
    }

    /**
     * Compare screenshots
     * @return []
     */
    private function compare()
    {
        $differenceFiles = [];
        foreach($this->paired as $file) {
            $tempOutput = $this->localPath.'/'.md5(uniqid(rand(), true));
            $filesSize = [];
            foreach([$this->globalPath.$file, $this->localPath.$file] as $fileToIdentify) {
                $identifyCommand = $this->identifyTool." {$fileToIdentify}";
                $log = [];
                exec($identifyCommand, $log, $status);
                $filesSize[] = explode(' ', $log[0])[2];
            }
            if (1 !== count(array_unique($filesSize))) {
                $differenceFiles[] = $file;
                continue;
            }

            $status = null;
            $compareCommand =
                $this->compareTool
                ." -metric mae {$this->globalPath}{$file} {$this->localPath}{$file} "
                ."{$tempOutput} 2> {$this->dummyFilename}";
            exec($compareCommand, $dummy, $status);
            if (0 !== $status) {
                $differenceFiles[] = $file;
            }
            unlink($tempOutput);
            unlink($this->dummyFilename);
        }

        return $differenceFiles;
    }
}
