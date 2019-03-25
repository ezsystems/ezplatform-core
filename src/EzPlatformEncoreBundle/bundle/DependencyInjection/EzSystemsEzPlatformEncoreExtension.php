<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformEncoreBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class EzSystemsEzPlatformEncoreExtension extends Extension
{
    const EZ_ENCORE_CONFIG_NAME = 'ez.config.js';
    const EZ_ENCORE_MANAGER_NAME = 'ez.config.manager.js';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');
        $rootPath = dirname($container->getParameter('kernel.root_dir'));
        $targetPath = 'var/encore';

        $this->dumpConfigurationPathsToFile($rootPath, $targetPath, $bundlesMetadata);
        $this->dumpConfigurationManagerPathsToFile($rootPath, $targetPath, $bundlesMetadata);
    }

    /**
     * Looks for Resources/encore/ez.config.js file in every registered and enabled bundle.
     * Dumps json list of paths to files it finds.
     *
     * @param string $rootPath
     * @param string $targetPath Where to put eZ Encore paths configuration file (default: var/encore/ez.config.js)
     * @param array $bundlesMetadata
     */
    public function dumpConfigurationPathsToFile(string $rootPath, string $targetPath, array $bundlesMetadata): void
    {
        $finder = new Finder();
        $filesystem = new Filesystem();
        $paths = [];

        $finder
            ->in(array_column($bundlesMetadata, 'path'))
            ->path('Resources/encore')
            ->name(self::EZ_ENCORE_CONFIG_NAME)
            ->files();

        /** @var \Symfony\Component\Finder\SplFileInfo $fileInfo */
        foreach ($finder as $fileInfo) {
            $paths[] = preg_replace(
                '/^' . preg_quote($rootPath, '/') . '/',
                '.',
                $fileInfo->getRealPath()
            );
        }

        $filesystem->mkdir($targetPath);
        $filesystem->dumpFile(
            $rootPath . '/' . $targetPath . '/' . self::EZ_ENCORE_CONFIG_NAME,
            sprintf('module.exports = %s;', json_encode($paths))
        );
    }

    /**
     * Looks for Resources/encore/ez.config.manager.js file in every registered and enabled bundle.
     * Dumps json list of paths to files it finds.
     *
     * @param string $rootPath
     * @param string $targetPath Where to put eZ Encore paths manager file (default: var/encore/ez.config.manager.js)
     * @param array $bundlesMetadata
     */
    public function dumpConfigurationManagerPathsToFile(string $rootPath, string $targetPath, array $bundlesMetadata): void
    {
        $finder = new Finder();
        $filesystem = new Filesystem();
        $paths = [];

        $finder
            ->in(array_column($bundlesMetadata, 'path'))
            ->path('Resources/encore')
            ->name(self::EZ_ENCORE_MANAGER_NAME)
            ->files();

        /** @var \Symfony\Component\Finder\SplFileInfo $fileInfo */
        foreach ($finder as $fileInfo) {
            $paths[] = preg_replace(
                '/^' . preg_quote($rootPath, '/') . '/',
                '.',
                $fileInfo->getRealPath()
            );
        }

        $filesystem->mkdir($targetPath);
        $filesystem->dumpFile(
            $rootPath . '/' . $targetPath . '/' . self::EZ_ENCORE_MANAGER_NAME,
            sprintf('module.exports = %s;', json_encode($paths))
        );
    }
}
