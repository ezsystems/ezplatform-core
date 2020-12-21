<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformCoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;

final class EzPlatformCoreExtension extends Extension implements PrependExtensionInterface
{
    public const EXTENSION_ALIAS = 'ezplatform';

    public function load(array $configs, ContainerBuilder $container): void
    {
    }

    public function getConfiguration(
        array $config,
        ContainerBuilder $container
    ): ConfigurationInterface {
        return new Configuration();
    }

    public function getAlias(): string
    {
        return self::EXTENSION_ALIAS;
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->configureGenericSetup($container);
        $this->configurePlatformShSetup($container);

        // inject "ezplatform" extension settings into "ezpublish" extension
        // configuration here is zero-based array of configurations from multiple sources
        // to be merged by "ezpublish" extension
        // @todo remove once we replace EzPublishCoreBundle with EzPlatformCoreBundle
        foreach ($container->getExtensionConfig('ezplatform') as $eZPlatformConfig) {
            $container->prependExtensionConfig('ezpublish', $eZPlatformConfig);
        }
    }

    private function configureGenericSetup(ContainerBuilder $container): void
    {
        $projectDir = $container->getParameter('kernel.project_dir');

        if ($_SERVER['DFS_NFS_PATH'] ?? false) {
            $loader = new Loader\YamlFileLoader($container, new FileLocator($projectDir . '/config/packages/dfs'));
            $loader->load('dfs.yaml');
        }

        // Cache settings
        // If CACHE_POOL env variable is set, check if there is a yml file that needs to be loaded for it
        if (($pool = $_SERVER['CACHE_POOL'] ?? false) && file_exists($projectDir . "/config/packages/cache_pool/${pool}.yaml")) {
            $loader = new Loader\YamlFileLoader($container, new FileLocator($projectDir . '/config/packages/cache_pool'));
            $loader->load($pool . '.yaml');
        }
    }

    private function configurePlatformShSetup(ContainerBuilder $container): void
    {
        $projectDir = $container->getParameter('kernel.project_dir');

        // Will not be executed on build step
        $relationships = $_SERVER['PLATFORM_RELATIONSHIPS'] ?? false;
        if (!$relationships) {
            return;
        }

        $relationships = json_decode(base64_decode($relationships), true);

        // PLATFORMSH_DFS_NFS_PATH is different compared to DFS_NFS_PATH in the sense that it is relative to ezplatform dir
        // DFS_NFS_PATH is an absolute path
        if ($dfsNfsPath = $_SERVER['PLATFORMSH_DFS_NFS_PATH'] ?? false) {
            $loader = new Loader\YamlFileLoader($container, new FileLocator($projectDir . '/config/packages/dfs'));
            $loader->load('dfs.yaml');
        }

        // Use Redis-based caching if possible.
        if (isset($relationships['rediscache'])) {
            foreach ($relationships['rediscache'] as $endpoint) {
                if ($endpoint['scheme'] !== 'redis') {
                    continue;
                }

                $loader = new Loader\YamlFileLoader($container, new FileLocator($projectDir . '/config/packages/cache_pool'));
                $loader->load('cache.redis.yaml');
            }
        } elseif (isset($relationships['cache'])) {
            // Fallback to memcached if here (deprecated, we will only handle redis here in the future)
            foreach ($relationships['cache'] as $endpoint) {
                if ($endpoint['scheme'] !== 'memcached') {
                    continue;
                }

                $loader = new Loader\YamlFileLoader($container, new FileLocator($projectDir . '/config/packages/cache_pool'));
                $loader->load('cache.memcached.yaml');
            }
        }
    }
}
