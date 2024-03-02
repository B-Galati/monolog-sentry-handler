<?php

declare(strict_types=1);

namespace BGalati\MonologSentryHandler\Tests;

use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use Sentry\Event;
use Sentry\Options;
use Sentry\Response;
use Sentry\ResponseStatus;
use Sentry\Transport\TransportFactoryInterface;
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

    public function send(Event $event): PromiseInterface
    {
        $this->spiedEvent = $event;

        return new FulfilledPromise(new Response(ResponseStatus::skipped(), $event));
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

    public function close(?int $timeout = null): PromiseInterface
    {
        $this->isFlushed = true;

        return new FulfilledPromise(true);
    }
}

class FakeTransportFactory implements TransportFactoryInterface
{
    /**
     * @var SpyTransport
     */
    private $transport;

    public function __construct(SpyTransport $transport)
    {
        $this->transport = $transport;
    }

    public function create(Options $options): TransportInterface
    {
        return $this->transport;
    }
}
