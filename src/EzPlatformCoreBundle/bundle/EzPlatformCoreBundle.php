<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformCoreBundle;

use EzSystems\EzPlatformCoreBundle\DependencyInjection\Compiler\GenericConfigPass;
use EzSystems\EzPlatformCoreBundle\DependencyInjection\Compiler\PlatformShConfigPass;
use EzSystems\EzPlatformCoreBundle\DependencyInjection\EzPlatformCoreExtension;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class EzPlatformCoreBundle extends Bundle
{
    /**
     * Ibexa DXP Version.
     */
    public const VERSION = '3.3.0';

    public function getContainerExtension(): ExtensionInterface
    {
        return new EzPlatformCoreExtension();
    }

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new GenericConfigPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1000);
        $container->addCompilerPass(new PlatformShConfigPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1000);
    }
}
