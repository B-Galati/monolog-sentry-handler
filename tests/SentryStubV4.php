<?php

declare(strict_types=1);

namespace BGalati\MonologSentryHandler\Tests;

use Sentry\Event;
use Sentry\Transport\Result;
use Sentry\Transport\ResultStatus;
use Sentry\Transport\TransportInterface;

class SpyTransport implements TransportInterface
{
    /**
     * @var Event|null
     */
    public $spiedEvent;

    /**
     * @var bool
     */
    public $isFlushed = false;

    public function send(Event $event): Result
    {
        $this->spiedEvent = $event;

        return new Result(ResultStatus::skipped(), $event);
    }

    public function resetSpy(): void
    {
        $this->spiedEvent = null;
        $this->isFlushed = false;
    }

    public function getSpiedEvent(): Event
    {
        if (null === $this->spiedEvent) {
            throw new \RuntimeException('No spied scope');
        }

        return $this->spiedEvent;
    }

    public function close(?int $timeout = null): Result
    {
        $this->isFlushed = true;

        return new Result(ResultStatus::success());
    }
}
