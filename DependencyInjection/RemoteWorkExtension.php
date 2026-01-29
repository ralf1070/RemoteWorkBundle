<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle\DependencyInjection;

use App\Plugin\AbstractPluginExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;

class RemoteWorkExtension extends AbstractPluginExtension implements PrependExtensionInterface
{
    /**
     * @param array<mixed> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->registerBundleConfiguration($container, $config);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('kimai', [
            'permissions' => [
                'roles' => [
                    'ROLE_USER' => [
                        'view_own_remote_work',
                        'create_own_remote_work',
                        'edit_own_remote_work',
                        'delete_own_remote_work',
                    ],
                    'ROLE_TEAMLEAD' => [
                        'view_own_remote_work',
                        'create_own_remote_work',
                        'edit_own_remote_work',
                        'delete_own_remote_work',
                        'view_other_remote_work',
                        'approve_remote_work',
                    ],
                    'ROLE_ADMIN' => [
                        'view_own_remote_work',
                        'create_own_remote_work',
                        'edit_own_remote_work',
                        'delete_own_remote_work',
                        'view_other_remote_work',
                        'edit_other_remote_work',
                        'delete_other_remote_work',
                        'approve_remote_work',
                        'remote_work_settings',
                    ],
                    'ROLE_SUPER_ADMIN' => [
                        'view_own_remote_work',
                        'create_own_remote_work',
                        'edit_own_remote_work',
                        'delete_own_remote_work',
                        'view_other_remote_work',
                        'edit_other_remote_work',
                        'delete_other_remote_work',
                        'approve_remote_work',
                        'remote_work_settings',
                    ],
                ],
            ],
        ]);
    }
}
