<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformTemplating\Core\View;

use EzSystems\EzPlatformTemplating\SPI\View\VariableProvider;
use EzSystems\EzPlatformTemplating\SPI\View\VariableProviderRegistry as ParameterProviderRegistryInterface;
use Traversable;

final class VariableProviderRegistry implements ParameterProviderRegistryInterface
{
    /** @var \EzSystems\EzPlatformTemplating\SPI\View\VariableProvider[] */
    private $twigVariableProviders;

    public function __construct(Traversable $twigVariableProviders)
    {
        foreach ($twigVariableProviders as $twigVariableProvider) {
            $this->setTwigVariableProvider($twigVariableProvider);
        }
    }

    public function setTwigVariableProvider(VariableProvider $twigVariableProvider): void
    {
        $this->twigVariableProviders[$twigVariableProvider->getIdentifier()] = $twigVariableProvider;
    }

    public function getTwigVariableProvider(string $identifier): ?VariableProvider
    {
        return $this->twigVariableProviders[$identifier] ?? null;
    }

    public function hasTwigVariableProvider(string $identifier): bool
    {
        return isset($this->twigVariableProviders[$identifier]);
    }
}
