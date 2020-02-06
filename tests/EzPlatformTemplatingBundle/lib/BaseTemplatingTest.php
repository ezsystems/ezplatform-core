<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformTemplating;

use ArrayIterator;
use eZ\Publish\Core\MVC\Symfony\View\ContentView;
use eZ\Publish\Core\MVC\Symfony\View\View;
use eZ\Publish\Core\Repository\Values\Content\Content;
use eZ\Publish\Core\Repository\Values\Content\Location;
use EzSystems\EzPlatformTemplating\Core\View\VariableProviderRegistry;
use EzSystems\EzPlatformTemplating\SPI\View\VariableProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class BaseTemplatingTest extends TestCase
{
    protected function getContentViewMock(): MockObject
    {
        $view = $this->createMock(ContentView::class);

        $view->method('getContent')->willReturn(new Content());
        $view->method('getLocation')->willReturn(new Location());

        return $view;
    }

    protected function getRegistry(array $providers): VariableProviderRegistry
    {
        return new VariableProviderRegistry(
            new ArrayIterator($providers)
        );
    }

    protected function getProvider(string $identifier): VariableProvider
    {
        return new class($identifier) implements VariableProvider {
            private $identifier;

            public function __construct(string $identifier)
            {
                $this->identifier = $identifier;
            }

            public function getIdentifier(): string
            {
                return $this->identifier;
            }

            public function getTwigVariables(View $view, array $options = []): object
            {
                return (object)[
                    $this->identifier . '_parameter' => $this->identifier . '_value',
                ];
            }
        };
    }
}
