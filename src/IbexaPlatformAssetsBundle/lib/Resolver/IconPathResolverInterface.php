<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Platform\Assets\Resolver;

interface IconPathResolverInterface
{
    public function resolve(string $icon, ?string $set = null): string;
}
