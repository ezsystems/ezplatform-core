<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformTemplating\View;

use EzSystems\Tests\EzPlatformTemplating\BaseTemplatingTest;

final class VariableProviderRegistryTest extends BaseTemplatingTest
{
    public function testParameterProviderGetter(): void
    {
        $registry = $this->getRegistry([
            $this->getProvider('provider_a'),
            $this->getProvider('provider_b'),
        ]);

        $providerA = $registry->getTwigVariableProvider('provider_a');
        $providerB = $registry->getTwigVariableProvider('provider_b');
        $providerC = $registry->getTwigVariableProvider('provider_c');

        $this->assertEquals($providerA->getIdentifier(), 'provider_a');
        $this->assertEquals($providerB->getIdentifier(), 'provider_b');
        $this->assertNull($providerC);
    }

    public function testParameterProviderSetter(): void
    {
        $registry = $this->getRegistry([
            $this->getProvider('provider_a'),
            $this->getProvider('provider_b'),
        ]);

        $providerC = $registry->getTwigVariableProvider('provider_c');

        $this->assertNull($providerC);

        $registry->setTwigVariableProvider($this->getProvider('provider_c'));

        $providerC = $registry->getTwigVariableProvider('provider_c');
        $this->assertEquals($providerC->getIdentifier(), 'provider_c');
    }

    public function testParameterProviderChecker(): void
    {
        $registry = $this->getRegistry([
            $this->getProvider('provider_a'),
            $this->getProvider('provider_b'),
        ]);

        $this->assertTrue($registry->hasTwigVariableProvider('provider_a'));
        $this->assertTrue($registry->hasTwigVariableProvider('provider_b'));
        $this->assertFalse($registry->hasTwigVariableProvider('provider_c'));
    }
}
