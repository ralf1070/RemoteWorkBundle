<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('remote_work');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('approval_required')
                    ->defaultFalse()
                ->end()
                ->booleanNode('caldav_enabled')
                    ->defaultFalse()
                ->end()
                ->scalarNode('caldav_url')
                    ->defaultValue('')
                ->end()
                ->scalarNode('caldav_username')
                    ->defaultValue('')
                ->end()
                ->scalarNode('caldav_password')
                    ->defaultValue('')
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
