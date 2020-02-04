<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformTemplatingBundle;

use EzSystems\EzPlatformTemplatingBundle\EzPlatformTemplatingBundle;
use PHPUnit\Framework\TestCase;

final class EzPlatformTemplatingBundleTest extends TestCase
{
    public function testInstantiateBundle(): void
    {
        $bundle = new EzPlatformTemplatingBundle();
        self::assertEquals('EzPlatformTemplatingBundle', $bundle->getName());
    }
}
