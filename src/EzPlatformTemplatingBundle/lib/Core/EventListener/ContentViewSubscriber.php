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
use EzSystems\EzPlatformTemplating\SPI\View\VariableProviderRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class ContentViewSubscriber implements EventSubscriberInterface
{
    public const TWIG_VARIABLES_KEY = 'twig_variables';

    private const PARAMETER_EXPRESSION_KEY = '_expression';
    private const PARAMETER_PROVIDER_KEY = '_provider';

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
        $twigVariables = $view->getConfigHash()[self::TWIG_VARIABLES_KEY] ?? [];

        foreach ($twigVariables as $name => &$twigVariable) {
            if ($this->isExpressionParameter($twigVariable)) {
                $twigVariable = $this->expressionLanguage->evaluate($twigVariable[self::PARAMETER_EXPRESSION_KEY], [
                    'view' => $view,
                    'parameters' => $view->getParameters(),
                    'content' => $view->getContent(),
                    'location' => $view->getLocation(),
                    'config' => $this->configResolver,
                ]);
            } elseif ($this->isProviderParameter($twigVariable)) {
                $provider = $this->parameterProviderRegistry->getTwigVariableProvider($twigVariable[self::PARAMETER_PROVIDER_KEY]);
                $twigVariable = $provider->getTwigVariables($view, $twigVariable ?? []);
            }
        }

        $view->setParameters(array_replace($view->getParameters() ?? [], $twigVariables));
    }

    private function isExpressionParameter($twigVariable): bool
    {
        return !empty($twigVariable[self::PARAMETER_EXPRESSION_KEY])
            && \is_string($twigVariable[self::PARAMETER_EXPRESSION_KEY]);
    }

    private function isProviderParameter($twigVariable): bool
    {
        return !empty($twigVariable[self::PARAMETER_PROVIDER_KEY])
            && $this->parameterProviderRegistry->hasTwigVariableProvider($twigVariable[self::PARAMETER_PROVIDER_KEY]);
    }
}
