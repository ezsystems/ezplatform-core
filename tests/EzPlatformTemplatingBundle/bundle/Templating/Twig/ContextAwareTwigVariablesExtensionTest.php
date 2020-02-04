<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformTemplatingBundle\Templating\Twig;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use EzSystems\EzPlatformTemplatingBundle\Templating\Twig\ContextAwareTwigVariablesExtension;
use PHPUnit\Framework\TestCase;

class ContextAwareTwigVariablesExtensionTest extends TestCase
{
    public function testNoVariables(): void
    {
        $configResolverMock = $this->createMock(ConfigResolverInterface::class);

        $extension = new ContextAwareTwigVariablesExtension($configResolverMock);

        $this->assertSame($extension->getGlobals(), []);
    }

    public function testVariables(): void
    {
        $configResolverMock = $this->createMock(ConfigResolverInterface::class);

        $configResolverMock->method('hasParameter')
            ->with('twig_variables')
            ->willReturn(true);

        $configResolverMock->method('getParameter')
            ->with('twig_variables')
            ->willReturn([
                'some' => 'global',
                'variable' => 'value',
            ]);

        $extension = new ContextAwareTwigVariablesExtension($configResolverMock);

        $this->assertSame($extension->getGlobals(), [
            'some' => 'global',
            'variable' => 'value',
        ]);
    }
}
