<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds remote work icons to the tabler bundle configuration.
 *
 * This must be done via CompilerPass because TablerExtension::prepend()
 * reads the icons before plugin prepend() methods are called.
 */
final class IconsCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('tabler_bundle.icons')) {
            return;
        }

        $icons = $container->getParameter('tabler_bundle.icons');

        // Add remote work icons (use underscore - the icon filter converts hyphens to underscores)
        $icons['homeoffice'] = 'fas fa-home';
        $icons['business_trip'] = 'fas fa-car';

        $container->setParameter('tabler_bundle.icons', $icons);
    }
}
