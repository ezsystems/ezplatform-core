<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformTemplating\Core\EventListener;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\MVC\Symfony\Event\PreContentViewEvent;
use eZ\Publish\Core\MVC\Symfony\MVCEvents;
use eZ\Publish\Core\MVC\Symfony\View\View;
use EzSystems\EzPlatformTemplating\SPI\View\VariableProviderRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class ContentViewSubscriber implements EventSubscriberInterface
{
    private const EXPRESSION_INDICATOR = '@=';

    public const PARAMETERS_KEY = 'params';

    /** @var \EzSystems\EzPlatformTemplating\SPI\View\VariableProviderRegistry */
    private $parameterProviderRegistry;

    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    private $configResolver;

    /** @var \Symfony\Component\ExpressionLanguage\ExpressionLanguage */
    private $expressionLanguage;

    public function __construct(
        VariableProviderRegistry $parameterProviderRegistry,
        ConfigResolverInterface $configResolver
    ) {
        $this->parameterProviderRegistry = $parameterProviderRegistry;
        $this->configResolver = $configResolver;

        $this->expressionLanguage = new ExpressionLanguage();
        $this->expressionLanguage->register(
            'provider',
            function (string $identifier) {
                return 'Not implemented: Not a Dependency Injection expression';
            },
            function (array $variables, string $identifier) {
                /** @var \EzSystems\EzPlatformTemplating\SPI\View\VariableProvider $provider */
                $provider = $variables['providerRegistry']->getTwigVariableProvider($identifier);

                return $provider->getTwigVariables($variables['view'], $variables);
            }
        );
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MVCEvents::PRE_CONTENT_VIEW => 'onPreContentView',
        ];
    }

    public function onPreContentView(PreContentViewEvent $event): void
    {
        $view = $event->getContentView();
        $twigVariables = $view->getConfigHash()[self::PARAMETERS_KEY] ?? [];

        foreach ($twigVariables as $name => &$twigVariable) {
            $this->recursiveParameterProcessor($twigVariable, $view);
        }

        $view->setParameters(array_replace($view->getParameters() ?? [], $twigVariables));
    }

    private function recursiveParameterProcessor(&$twigVariable, View $view): void
    {
        if ($this->isExpressionParameter($twigVariable)) {
            $twigVariable = $this->expressionLanguage->evaluate($this->getExpression($twigVariable), [
                'view' => $view,
                'parameters' => $view->getParameters(),
                'content' => $view->getContent(),
                'location' => $view->getLocation(),
                'config' => $this->configResolver,
                'providerRegistry' => $this->parameterProviderRegistry,
            ]);
        } else if (is_array($twigVariable)) {
            foreach ($twigVariable as &$nestedTwigVariable) {
                $this->recursiveParameterProcessor($nestedTwigVariable, $view);
            }
        }
    }

    private function isExpressionParameter($twigVariable): bool
    {
        return is_string($twigVariable) && strpos($twigVariable, self::EXPRESSION_INDICATOR) === 0;
    }

    private function getExpression(string $twigVariable): string
    {
        return substr($twigVariable, strlen(self::EXPRESSION_INDICATOR));
    }
}
