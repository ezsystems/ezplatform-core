<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformCoreBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;

class GenericConfigPass implements CompilerPassInterface
{
    /**
     * On Symfony container compilation*, reads parameters from env variables if defined and overrides the yml parameters.
     * For typical use cases like Docker, make sure to recompile Symfony container on run to refresh settings.
     */
    public function process(ContainerBuilder $container)
    {
        $projectDir = $container->getParameter('kernel.project_dir');

        if ($dfsNfsPath = $_SERVER['DFS_NFS_PATH'] ?? false) {
            $container->setParameter('dfs_nfs_path', $dfsNfsPath);

            $parameterMap = [
                'dfs_database_charset' => 'database_charset',
                'dfs_database_driver' => 'database_driver',
                'dfs_database_collation' => 'database_collation',
            ];

            foreach ($parameterMap as $dfsParameter => $platformParameter) {
                $container->setParameter(
                    $dfsParameter,
                    $_SERVER[strtoupper($dfsParameter)] ?? $container->getParameter($platformParameter)
                );
            }

            $loader = new Loader\YamlFileLoader($container, new FileLocator($projectDir . '/config/packages/dfs'));
            $loader->load('dfs.yaml');
        }

        // Cache settings
        // If CACHE_POOL env variable is set, check if there is a yml file that needs to be loaded for it
        if (($pool = $_SERVER['CACHE_POOL'] ?? false) && file_exists($projectDir . "/config/packages/cache_pool/${pool}.yaml")) {
            $loader = new Loader\YamlFileLoader($container, new FileLocator($projectDir . '/config/packages/cache_pool'));
            $loader->load($pool . '.yaml');
        }

        // Params that needs to be set at compile time and thus can't use Symfony's env()
        if ($purgeType = $_SERVER['HTTPCACHE_PURGE_TYPE'] ?? false) {
            $container->setParameter('purge_type', $purgeType);
        }

        if ($value = $_SERVER['MAILER_TRANSPORT'] ?? false) {
            $container->setParameter('mailer_transport', $value);
        }

        if ($value = $_SERVER['LOG_TYPE'] ?? false) {
            $container->setParameter('log_type', $value);
        }

        if ($value = $_SERVER['SESSION_HANDLER_ID'] ?? false) {
            $container->setParameter('ezplatform.session.handler_id', $value);
        }

        if ($value = $_SERVER['SESSION_SAVE_PATH'] ?? false) {
            $container->setParameter('ezplatform.session.save_path', $value);
        }
    }
}
