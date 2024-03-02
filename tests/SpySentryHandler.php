<?php

declare(strict_types=1);

namespace BGalati\MonologSentryHandler\Tests;

use BGalati\MonologSentryHandler\SentryHandler;
use Sentry\Event;
use Sentry\State\Scope;

class SpySentryHandler extends SentryHandler
{
    /**
     * @var bool
     */
    public $afterWriteCalled = false;

    protected function processScope(Scope $scope, $record, Event $sentryEvent): void
    {
        $scope->setExtra('processScope', 'called');
    }

    protected function afterWrite(): void
    {
        $this->afterWriteCalled = true;

        parent::afterWrite();
    }

    public function resetSpy(): void
    {
        $this->afterWriteCalled = false;
    }
}
