<?php

declare(strict_types=1);

namespace Homeapp\MonologSentryHandler;

use Sentry\Event as SentryEvent;
use Sentry\State\Scope;

/**
 * ScopeProcessor is called when Sentry event is captured by the handler.
 * Override it if you want to add custom data to Sentry $scope.
 */
interface ScopeProcessor
{
    /**
     * @param Scope       $scope       Sentry scope where you can add custom data
     * @param array       $record      Current monolog record
     * @param SentryEvent $sentryEvent Current sentry event that will be captured
     */
    public function processScope(Scope $scope, array $record, SentryEvent $sentryEvent): void;
}
