<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Platform\Bundle\Assets;

use Ibexa\Platform\Bundle\Assets\DependencyInjection\Configuration\Parser;
use Ibexa\Platform\Bundle\Assets\DependencyInjection\IbexaPlatformAssetsExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class IbexaPlatformAssetsBundle extends Bundle
{
    public function getContainerExtension(): ExtensionInterface
    {
        return new IbexaPlatformAssetsExtension();
    }

    public function build(ContainerBuilder $container)
    {
        /** @var \eZ\Bundle\EzPublishCoreBundle\DependencyInjection\EzPublishCoreExtension $kernelExtension */
        $kernelExtension = $container->getExtension('ezpublish');

        $kernelExtension->addConfigParser(new Parser\Assets());
    }
}
