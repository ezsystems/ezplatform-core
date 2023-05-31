<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformCoreBundle;

use EzSystems\EzPlatformCoreBundle\DependencyInjection\Compiler\SessionConfigurationPass;
use EzSystems\EzPlatformCoreBundle\DependencyInjection\EzPlatformCoreExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class EzPlatformCoreBundle extends Bundle
{
    /**
     * Ibexa DXP Version.
     */
    public const VERSION = '3.3.34';

    public function getContainerExtension(): ExtensionInterface
    {
        return new EzPlatformCoreExtension();
    }

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new SessionConfigurationPass());
    }
}
