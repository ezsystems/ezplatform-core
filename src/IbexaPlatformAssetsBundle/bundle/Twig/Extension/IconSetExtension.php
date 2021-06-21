<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Platform\Bundle\Assets\Twig\Extension;

use Ibexa\Platform\Assets\Resolver\IconPathResolverInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class IconSetExtension extends AbstractExtension
{
    /** @var \Ibexa\Platform\Assets\Resolver\IconPathResolverInterface */
    private $iconPathResolver;

    public function __construct(IconPathResolverInterface $iconPathResolver)
    {
        $this->iconPathResolver = $iconPathResolver;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'ez_icon_path',
                [$this, 'getIconPath'],
                [
                    'is_safe' => ['html'],
                    'deprecated' => true,
                    'alternative' => 'ibexa_icon_path',
                ],
            ),
            new TwigFunction(
                'ibexa_icon_path',
                [$this, 'getIconPath'],
                [
                    'is_safe' => ['html'],
                ]
            ),
        ];
    }

    public function getIconPath(string $icon, string $set = null): string
    {
        return $this->iconPathResolver->resolve($icon, $set);
    }
}
