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
 * Screenshots copying service
 * @author Wojciech Surmacz <wojciech.surmacz@gmail.com>
 */
class Copy extends Base
{
	/**
	 * @var string
	 */
    private $globalPath;

    /**
     * @var string
     */
    private $localPath;

    /**
     * (non-PHPdoc)
     * @see \Surmacz\FrontendConsistencyBundle\Services\Base::process()
     */
    public function process($globalPath, $localPath)
    {
        $this->globalPath = $globalPath;
        $this->localPath = $localPath;
        $this->clean($this->globalPath);
        $this->copy();
    }

    /**
     * Copy locals to globals
     */
    private function copy()
    {
        $this->clean($this->globalPath);
        foreach(
            $this->cleanPrefix(
                $this->getFiles($this->localPath),
                $this->localPath
            ) as $file
        ) {
            $this->filesystem->copy($this->localPath.$file, $this->globalPath.$file);
        }
    }

    /**
     * @param string $dir
     */
    private function clean($dir)
    {
        $this->filesystem->remove($this->getFiles($dir));
    }
}
