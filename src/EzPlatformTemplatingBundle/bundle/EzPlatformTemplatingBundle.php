<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformTemplatingBundle;

use EzSystems\EzPlatformTemplatingBundle\DependencyInjection\Configuration\Parser\TwigVariablesParser;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EzPlatformTemplatingBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        /** @var \eZ\Bundle\EzPublishCoreBundle\DependencyInjection\EzPublishCoreExtension $kernelExtension */
        $kernelExtension = $container->getExtension('ezplatform');

        $configParsers = $this->getConfigParsers();
        array_walk($configParsers, [$kernelExtension, 'addConfigParser']);
    }

    private function getConfigParsers(): array
    {
        return [
            new TwigVariablesParser(),
        ];
    }
}
