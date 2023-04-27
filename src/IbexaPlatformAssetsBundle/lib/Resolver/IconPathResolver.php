<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Platform\Assets\Resolver;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\MVC\Symfony\Event\ScopeChangeEvent;
use eZ\Publish\Core\MVC\Symfony\MVCEvents;
use Symfony\Component\Asset\Packages;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
final class IconPathResolver implements IconPathResolverInterface, EventSubscriberInterface
{
    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    private $configResolver;

    /** @var \Symfony\Component\Asset\Packages */
    private $packages;

    /** @var string[] */
    private $iconCache;

    public function __construct(
        ConfigResolverInterface $configResolver,
        Packages $packages
    ) {
        $this->configResolver = $configResolver;
        $this->packages = $packages;
        $this->iconCache = [];
    }

    public function resolve(string $icon, ?string $set = null): string
    {
        if (isset($this->iconCache[$set][$icon])) {
            return $this->iconCache[$set][$icon];
        }

        $iconSetName = $set ?? $this->configResolver->getParameter('assets.default_icon_set');
        $iconSets = $this->configResolver->getParameter('assets.icon_sets');

        $this->iconCache[$set][$icon] = sprintf('%s#%s', $this->packages->getUrl($iconSets[$iconSetName]), $icon);

        return $this->iconCache[$set][$icon];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MVCEvents::CONFIG_SCOPE_CHANGE => ['onConfigScopeChange', 100],
            MVCEvents::CONFIG_SCOPE_RESTORE => ['onConfigScopeChange', 100],
        ];
    }

    public function onConfigScopeChange(ScopeChangeEvent $event): void
    {
        $this->iconCache = [];
    }
}
