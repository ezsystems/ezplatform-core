<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformCoreBundle\DependencyInjection\Compiler;

use EzSystems\EzPlatformCoreBundle\DependencyInjection\Compiler\SessionConfigurationPass;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\AbstractSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\SessionHandlerFactory;

class SessionConfigurationPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new SessionConfigurationPass());
    }

    public function testCompile(): void
    {
        $this->container->setDefinition(
            'session.storage.native',
            (new Definition())->setArguments([null, null, null])
        );
        $this->container->setDefinition(
            'session.storage.php_bridge',
            (new Definition())->setArguments([null, null])
        );
        $this->container->setParameter('ezplatform.session.handler_id', 'my_handler');
        $this->container->setParameter('ezplatform.session.save_path', 'my_save_path');

        $this->compile();

        $this->assertContainerBuilderHasAlias('session.handler', 'my_handler');
        $this->assertContainerBuilderHasParameter('session.save_path', 'my_save_path');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'session.storage.native',
            1,
            new Reference('session.handler')
        );
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'session.storage.php_bridge',
            0,
            new Reference('session.handler')
        );
    }

    public function testCompileWithDsn(): void
    {
        $dsn = 'redis://instance.local:1234';

        $definition = new Definition(AbstractSessionHandler::class);
        $definition->setFactory([SessionHandlerFactory::class, 'createHandler']);
        $definition->setArguments([$dsn]);

        $this->container->setDefinition('session.abstract_handler', $definition);
        $this->container->setParameter('ezplatform.session.handler_id', $dsn);
        $this->container->setDefinition(
            'session.storage.native',
            (new Definition())->setArguments([null, null, null])
        );
        $this->container->setDefinition(
            'session.storage.php_bridge',
            (new Definition())->setArguments([null, null])
        );

        $this->compile();

        $this->assertContainerBuilderHasAlias('session.handler', 'session.abstract_handler');
    }

    public function testCompileWithNullValues(): void
    {
        $this->container->setParameter('ezplatform.session.handler_id', null);
        $this->container->setParameter('ezplatform.session.save_path', null);

        $this->compile();

        $this->assertContainerBuilderNotHasService('session.handler');
        self::assertNotTrue($this->container->hasParameter('session.save_path'));
    }
}
