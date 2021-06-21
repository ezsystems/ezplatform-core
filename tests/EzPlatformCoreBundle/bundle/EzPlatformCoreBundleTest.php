<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformCoreBundle;

use EzSystems\EzPlatformCoreBundle\EzPlatformCoreBundle;
use PHPUnit\Framework\TestCase;

final class EzPlatformCoreBundleTest extends TestCase
{
    public function testInstantiateBundle(): void
    {
        $bundle = new EzPlatformCoreBundle();
        self::assertEquals('EzPlatformCoreBundle', $bundle->getName());
        self::assertEquals('ezplatform', $bundle->getContainerExtension()->getAlias());
    }
}
