<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
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
    const CONFIG_NAMES = [
        'ez.config.js',
        'ez.config.manager.js',
        'ez.webpack.custom.config.js',
    ];

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');
        $rootPath = \dirname($container->getParameter('kernel.root_dir')) . '/';
        $targetPath = 'var/encore';

        foreach (self::CONFIG_NAMES as $configName) {
            $this->dumpConfigurationPathsToFile($configName, $rootPath, $targetPath, $bundlesMetadata);
        }
    }

    /**
     * Looks for Resources/encore/ files in every registered and enabled bundle.
     * Dumps json list of paths to files it finds.
     *
     * @param string $targetPath Where to put eZ Encore paths configuration file (default: var/encore)
     */
    public function dumpConfigurationPathsToFile(string $configName, string $rootPath, string $targetPath, array $bundlesMetadata): void
    {
        $finder = new Finder();
        $filesystem = new Filesystem();
        $paths = [];

        $finder
            ->in(array_column($bundlesMetadata, 'path'))
            ->path('Resources/encore')
            ->name($configName)
            ->files();

        /** @var \Symfony\Component\Finder\SplFileInfo $fileInfo */
        foreach ($finder as $fileInfo) {
            $paths[] = preg_replace(
                '/^' . preg_quote($rootPath, '/') . '/',
                './',
                $fileInfo->getRealPath()
            );
        }

        $filesystem->mkdir($rootPath . '/' . $targetPath);
        $filesystem->dumpFile(
            $rootPath . $targetPath . '/' . $configName,
            sprintf('module.exports = %s;', json_encode($paths))
        );
    }
}
