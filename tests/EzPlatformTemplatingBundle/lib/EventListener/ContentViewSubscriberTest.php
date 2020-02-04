<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\Tests\EzPlatformTemplating\EventListener;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\MVC\Symfony\Event\PreContentViewEvent;
use eZ\Publish\Core\MVC\Symfony\View\View;
use EzSystems\EzPlatformTemplating\Core\EventListener\ContentViewSubscriber;
use EzSystems\EzPlatformTemplating\SPI\View\VariableProvider;
use EzSystems\Tests\EzPlatformTemplating\BaseTemplatingTest;

final class ContentViewSubscriberTest extends BaseTemplatingTest
{
    private function getContentViewMockSubscriber(): ContentViewSubscriber
    {
        return new ContentViewSubscriber(
            $this->getRegistry([
                $this->getProvider('test_provider'),
            ]),
            $this->createMock(ConfigResolverInterface::class)
        );
    }

    public function testWithoutVariables(): void
    {
        $view = $this->getContentViewMock();
        $event = new PreContentViewEvent($view);

        $view
            ->expects($this->once())
            ->method('setParameters')
            ->with([]);

        $subscriber = $this->getContentViewMockSubscriber();
        $subscriber->onPreContentView($event);
    }

    public function testWithScalarVariables(): void
    {
        $view = $this->getContentViewMock();
        $event = new PreContentViewEvent($view);

        $view->method('getConfigHash')
            ->willReturn([
                ContentViewSubscriber::TWIG_VARIABLES_KEY => [
                    'param_1' => 'scalar_1',
                    'param_2' => 2,
                    'param_3' => 3,
                ],
            ]);

        $view
            ->expects($this->once())
            ->method('setParameters')
            ->with([
                'param_1' => 'scalar_1',
                'param_2' => 2,
                'param_3' => 3,
            ]);

        $subscriber = $this->getContentViewMockSubscriber();
        $subscriber->onPreContentView($event);
    }

    public function testOverwriteVariables(): void
    {
        $view = $this->getContentViewMock();
        $event = new PreContentViewEvent($view);

        $view->method('getConfigHash')
            ->willReturn([
                ContentViewSubscriber::TWIG_VARIABLES_KEY => [
                    'param_1' => 'scalar_1',
                ],
            ]);

        $view->method('getParameters')
            ->willReturn([
                'param_1' => 'existing_value',
                'param_2' => 'also_existing_value',
            ]);

        $view
            ->expects($this->once())
            ->method('setParameters')
            ->with([
                'param_1' => 'scalar_1',
                'param_2' => 'also_existing_value',
            ]);

        $subscriber = $this->getContentViewMockSubscriber();
        $subscriber->onPreContentView($event);
    }

    public function testWithExpressionParam(): void
    {
        $view = $this->getContentViewMock();
        $event = new PreContentViewEvent($view);

        $randomNumber = rand(100, 200);

        $view->method('getParameters')
            ->willReturn([
                'random_number' => $randomNumber,
            ]);

        $view->method('getConfigHash')
            ->willReturn([
                ContentViewSubscriber::TWIG_VARIABLES_KEY => [
                    'plus_42' => [
                        '_expression' => 'parameters["random_number"] + 42',
                    ],
                ],
            ]);

        $view
            ->expects($this->once())
            ->method('setParameters')
            ->with([
                'random_number' => $randomNumber,
                'plus_42' => $randomNumber + 42,
            ]);

        $subscriber = $this->getContentViewMockSubscriber();
        $subscriber->onPreContentView($event);
    }

    public function testWithProviderAndOptionsParam(): void
    {
        $view = $this->getContentViewMock();
        $event = new PreContentViewEvent($view);

        $view->method('getParameters')
            ->willReturn([
                'meaning_of_life' => 42,
            ]);

        $view->method('getConfigHash')
            ->willReturn([
                ContentViewSubscriber::TWIG_VARIABLES_KEY => [
                    'provider_param' => [
                        '_provider' => 'provider_with_options',
                        'some_number' => 123,
                    ],
                ],
            ]);

        $view
            ->expects($this->once())
            ->method('setParameters')
            ->with([
                'meaning_of_life' => 42,
                'provider_param' => [
                    'result' => 165,
                ],
            ]);

        $subscriber = new ContentViewSubscriber(
            $this->getRegistry([
                new class() implements VariableProvider {
                    public function getIdentifier(): string
                    {
                        return 'provider_with_options';
                    }

                    public function getTwigVariables(
                        View $view,
                        array $options = []
                    ): array {
                        return [
                            'result' => $view->getParameters()['meaning_of_life'] + $options['some_number'],
                        ];
                    }
                },
            ]),
            $this->createMock(ConfigResolverInterface::class)
        );
        $subscriber->onPreContentView($event);
    }
}
