<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EncoreBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class EzSystemsEncoreExtension extends Extension
{
    const EZ_ENCORE_CONFIG_NAME = 'ez.config.js';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $this->dumpConfigurationPathsToFile(
            dirname($container->getParameter('kernel.root_dir')) . '/var/encore',
            $container->getParameter('kernel.bundles_metadata')
        );
    }

    /**
     * Looks for Resources/encore/ez.config.js file in every registered and enabled bundle.
     * Dumps json list of paths to files it finds.
     *
     * @param string $targetPath Where to put eZ Encore paths configuration file (default: var/encore/ez.config.js)
     * @param array $bundlesMetadata
     */
    public function dumpConfigurationPathsToFile(string $targetPath, array $bundlesMetadata): void
    {
        $finder = new Finder();
        $filesystem = new Filesystem();
        $paths = [];

        $finder
            ->in(array_column($bundlesMetadata, 'path'))
            ->path('Resources/encore')
            ->name(self::EZ_ENCORE_CONFIG_NAME)
            ->files();

        foreach ($finder as $fileInfo) {
            $paths[] = $fileInfo->getRealPath();
        }

        $filesystem->mkdir($targetPath);
        $filesystem->dumpFile(
            $targetPath . '/' . self::EZ_ENCORE_CONFIG_NAME,
            sprintf('module.exports = %s', json_encode($paths))
        );
    }
}
