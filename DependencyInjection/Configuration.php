<?php

/*
 * This file is part of Frontend Consistency Bundle
 *
 * (c) Wojciech Surmacz <wojciech.surmacz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Surmacz\FrontendConsistencyBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Bundle configuration
 * @author Wojciech Surmacz <wojciech.surmacz@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Config\Definition\ConfigurationInterface::getConfigTreeBuilder()
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('frontend_consistency');

        $rootNode
            ->children()
                ->scalarnode('browser_prefix_url')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->end()
                ->scalarnode('screenshot_global_path')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->defaultValue('consistency/screenshot/global')
                    ->end()
                ->scalarnode('screenshot_local_path')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->defaultValue('consistency/screenshot/local')
                    ->end()
                ->scalarnode('php_unit_path')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->end()
                ->scalarnode('php_unit_params')
                    ->isRequired()
                    ->end()
                ->scalarnode('image_compare_path')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->end()
                ->scalarnode('image_identify_path')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->end()
                ->arrayNode('environments')
                    ->requiresAtLeastOneElement()
                    ->prototype('array')
                        ->children()
                            ->scalarnode('path')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarnode('browser')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarnode('host')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->integerNode('port')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->integerNode('timeout')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
