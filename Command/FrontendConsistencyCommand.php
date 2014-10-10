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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Abstract Frontend Consistency Command
 * @author Wojciech Surmacz <wojciech.surmacz@gmail.com>
 */
abstract class FrontendConsistencyCommand extends ContainerAwareCommand
{
    /**
	 * @var string
	 */
    protected $topDir;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Console\Command\Command::initialize()
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->topDir = realpath(
            $this->getContainer()->get('kernel')->getRootDir().'/../'
        );
        $this->filesystem = new Filesystem();
    }
}
