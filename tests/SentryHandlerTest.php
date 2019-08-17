<?php

declare(strict_types=1);

namespace BGalati\MonologSentryHandler\Tests;

use BGalati\MonologSentryHandler\SentryHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Sentry\Breadcrumb;
use Sentry\ClientInterface;
use Sentry\Integration\IntegrationInterface;
use Sentry\Severity;
use Sentry\State\Hub;
use Sentry\State\HubInterface;
use Sentry\State\Scope;

class SentryHandlerTest extends TestCase
{
    private $hub;

    protected function setUp(): void
    {
        $client = $this->prophesize(ClientInterface::class);

        $this->hub = new SpyHub(new Hub($client->reveal()));
    }

    protected function tearDown(): void
    {
        $this->hub = null;
    }

    public function testHandle(): void
    {
        $handler = $this->createSentryHandler();

        $record = [
            'message'    => 'My info message',
            'context'    => [],
            'level'      => Logger::INFO,
            'level_name' => Logger::getLevelName(Logger::INFO),
            'channel'    => 'app',
            'extra'      => [],
        ];

        $handler->handle($record);

        $this->assertCapturedEvent(
            Severity::info(),
            'app.INFO: My info message',
            ['monolog.formatted' => 'app.INFO: My info message []']
        );
    }

    public function testHandleCaptureException(): void
    {
        $handler = $this->createSentryHandler();

        $record = [
            'message'    => 'My info message',
            'context'    => ['exception' => $exception = new \LogicException('Test logic exception')],
            'level'      => Logger::INFO,
            'level_name' => Logger::getLevelName(Logger::INFO),
            'channel'    => 'app',
            'extra'      => [],
        ];

        $handler->handle($record);

        $this->assertCapturedEvent(
            Severity::info(),
            'app.INFO: My info message',
            ['monolog.formatted' => 'app.INFO: My info message []'],
            $exception
        );
    }

    public function testHandleBatchDoesNotCallSentryIfNoRecordsAreProvided(): void
    {
        $handler = $this->createSentryHandler();
        $handler->handleBatch([]);

        $this->assertNull($this->hub->spiedScope);
        $this->assertNull($this->hub->spiedEvent);
    }

    public function testHandleBatch(): void
    {
        $handler = $this->createSentryHandler();

        $records = [
            [
                'message'    => 'Info message',
                'context'    => ['exception' => new \LogicException()],
                'level'      => $level = Logger::INFO,
                'level_name' => Logger::getLevelName($level),
                'channel'    => 'chan-info',
                'extra'      => ['extra-info'],
            ],
            [
                'message'    => 'Error Message',
                'context'    => [],
                'level'      => $level = Logger::ERROR,
                'level_name' => Logger::getLevelName($level),
                'channel'    => 'chan-error',
                'extra'      => ['extra-error'],
            ],
            [
                'message'    => 'Debug message',
                'context'    => [],
                'level'      => $level = Logger::DEBUG,
                'level_name' => Logger::getLevelName($level),
                'channel'    => 'chan-debug',
                'extra'      => ['extra-debug'],
            ],
            [
                'message'    => 'Emergency message',
                'context'    => [],
                'level'      => $level = Logger::EMERGENCY,
                'level_name' => Logger::getLevelName($level),
                'channel'    => 'chan-emerg',
                'extra'      => ['extra-emerg'],
            ],
            [
                'message'    => 'Warning message',
                'context'    => [],
                'level'      => $level = Logger::WARNING,
                'level_name' => Logger::getLevelName($level),
                'channel'    => 'chan-warn',
                'extra'      => ['extra-warn'],
            ],
            [
                'message'    => 'Notice message',
                'context'    => [],
                'level'      => $level = Logger::NOTICE,
                'level_name' => Logger::getLevelName($level),
                'channel'    => 'chan-notice',
                'extra'      => ['extra-notice'],
            ],
            [
                'message'    => 'Alert message',
                'context'    => [],
                'level'      => $level = Logger::ALERT,
                'level_name' => Logger::getLevelName($level),
                'channel'    => 'chan-alert',
                'extra'      => ['extra-alert'],
            ],
            [
                'message'    => 'Critical message',
                'context'    => ['exception' => new \LogicException()],
                'level'      => $level = Logger::CRITICAL,
                'level_name' => Logger::getLevelName($level),
                'channel'    => 'chan-critical',
                'extra'      => ['extra-critical'],
            ],
        ];

        $handler->handleBatch($records);

        $this->assertCapturedEvent(
            Severity::fatal(),
            'chan-emerg.EMERGENCY: Emergency message',
            ['monolog.formatted' => 'chan-emerg.EMERGENCY: Emergency message ["extra-emerg"]'],
            null,
            [
                [
                    'type'     => 'default',
                    'category' => 'chan-info',
                    'level'    => 'info',
                    'message'  => 'chan-info.INFO: Info message ["extra-info"]',
                    'data'     => [],
                ],
                [
                    'type'     => 'error',
                    'category' => 'chan-error',
                    'level'    => 'error',
                    'message'  => 'chan-error.ERROR: Error Message ["extra-error"]',
                    'data'     => [],
                ],
                [
                    'type'     => 'default',
                    'category' => 'chan-debug',
                    'level'    => 'debug',
                    'message'  => 'chan-debug.DEBUG: Debug message ["extra-debug"]',
                    'data'     => [],
                ],
                [
                    'type'     => 'error',
                    'category' => 'chan-emerg',
                    'level'    => 'critical',
                    'message'  => 'chan-emerg.EMERGENCY: Emergency message ["extra-emerg"]',
                    'data'     => [],
                ],
                [
                    'type'     => 'default',
                    'category' => 'chan-warn',
                    'level'    => 'warning',
                    'message'  => 'chan-warn.WARNING: Warning message ["extra-warn"]',
                    'data'     => [],
                ],
                [
                    'type'     => 'default',
                    'category' => 'chan-notice',
                    'level'    => 'info',
                    'message'  => 'chan-notice.NOTICE: Notice message ["extra-notice"]',
                    'data'     => [],
                ],
                [
                    'type'     => 'error',
                    'category' => 'chan-alert',
                    'level'    => 'critical',
                    'message'  => 'chan-alert.ALERT: Alert message ["extra-alert"]',
                    'data'     => [],
                ],
                [
                    'type'     => 'error',
                    'category' => 'chan-critical',
                    'level'    => 'critical',
                    'message'  => 'chan-critical.CRITICAL: Critical message ["extra-critical"]',
                    'data'     => [],
                ],
            ]
        );
    }

    public function testHandleBatchFiltersRecordsByLevel(): void
    {
        $handler = $this->createSentryHandler(Logger::WARNING);

        $records = [
            [
                'message'    => 'Info message',
                'context'    => ['exception' => new \LogicException()],
                'level'      => $level = Logger::INFO,
                'level_name' => Logger::getLevelName($level),
                'channel'    => 'test',
                'extra'      => [],
            ],
            [
                'message'    => 'Error Message',
                'context'    => [],
                'level'      => $level = Logger::ERROR,
                'level_name' => Logger::getLevelName($level),
                'channel'    => 'test',
                'extra'      => [],
            ],
            [
                'message'    => 'Debug message',
                'context'    => [],
                'level'      => $level = Logger::DEBUG,
                'level_name' => Logger::getLevelName($level),
                'channel'    => 'test',
                'extra'      => [],
            ],
            [
                'message'    => 'Warning message',
                'context'    => [],
                'level'      => $level = Logger::WARNING,
                'level_name' => Logger::getLevelName($level),
                'channel'    => 'test',
                'extra'      => [],
            ],
            [
                'message'    => 'Notice message',
                'context'    => [],
                'level'      => $level = Logger::NOTICE,
                'level_name' => Logger::getLevelName($level),
                'channel'    => 'test',
                'extra'      => [],
            ],
            [
                'message'    => 'Critical message',
                'context'    => ['exception' => $exception = new \LogicException('Exception of critical level')],
                'level'      => $level = Logger::CRITICAL,
                'level_name' => Logger::getLevelName($level),
                'channel'    => 'test',
                'extra'      => [],
            ],
        ];

        $handler->handleBatch($records);

        $this->assertCapturedEvent(
            Severity::fatal(),
            'test.CRITICAL: Critical message',
            ['monolog.formatted' => 'test.CRITICAL: Critical message []'],
            $exception,
            [
                [
                    'type'     => 'error',
                    'category' => 'test',
                    'level'    => 'error',
                    'message'  => 'test.ERROR: Error Message []',
                    'data'     => [],
                ],
                [
                    'type'     => 'default',
                    'category' => 'test',
                    'level'    => 'warning',
                    'message'  => 'test.WARNING: Warning message []',
                    'data'     => [],
                ],
                [
                    'type'     => 'error',
                    'category' => 'test',
                    'level'    => 'critical',
                    'message'  => 'test.CRITICAL: Critical message []',
                    'data'     => [],
                ],
            ]
        );
    }

    public function testHandleBatchCanBeCalledTwiceWithoutSideEffects(): void
    {
        $handler = $this->createSentryHandler();

        $records = [
            [
                'message'    => 'Info message',
                'context'    => [],
                'level'      => $level = Logger::INFO,
                'level_name' => Logger::getLevelName($level),
                'channel'    => 'test',
                'extra'      => [],
            ],
        ];

        $handler->handleBatch($records);
        $this->hub->resetSpy();
        $handler->handleBatch($records);

        $this->assertCapturedEvent(
            Severity::info(),
            'test.INFO: Info message',
            ['monolog.formatted' => 'test.INFO: Info message []'],
            null,
            [
                [
                    'type'     => 'default',
                    'category' => 'test',
                    'level'    => 'info',
                    'message'  => 'test.INFO: Info message []',
                    'data'     => [],
                ],
            ]
        );
    }

    private function assertCapturedEvent(Severity $severity, string $message, array $extra, \Exception $exception = null, array $breadcrumbs = []): void
    {
        $expectedEvent = [
            'level'   => $severity,
            'message' => $message,
        ];

        if (null !== $exception) {
            $expectedEvent['exception'] = $exception;
        }

        $this->assertEquals($expectedEvent, $this->hub->spiedEvent);
        $this->assertSame($breadcrumbs, $this->hub->getSpiedScopeBreadcrumbsAsArray());
        $this->assertSame($extra, $this->hub->spiedScope->getExtra());
        $this->assertEquals($severity, $this->hub->spiedScope->getLevel());
        $this->assertSame([], $this->hub->spiedScope->getTags());
        $this->assertSame([], $this->hub->spiedScope->getUser());
    }

    private function createSentryHandler(int $level = null): SentryHandler
    {
        if (null === $level) {
            $handler = new SentryHandler($this->hub);
        } else {
            $handler = new SentryHandler($this->hub, $level);
        }

        $handler->setFormatter(new LineFormatter('%channel%.%level_name%: %message% %extra%'));

        return $handler;
    }
}

class SpyHub implements HubInterface
{
    private $hub;

    /**
     * @var array|null
     */
    public $spiedEvent;

    /**
     * @var Scope|null
     */
    public $spiedScope;

    public function __construct(Hub $hub)
    {
        $this->hub = $hub;
    }

    public function resetSpy(): void
    {
        $this->spiedEvent = null;
        $this->spiedScope = null;
    }

    public function getSpiedScopeBreadcrumbsAsArray(): array
    {
        if (null === $this->spiedScope) {
            throw new \RuntimeException('No spied scope');
        }

        return array_map(
            function (Breadcrumb $breadcrumb) {
                $array = $breadcrumb->toArray();

                unset($array['timestamp']);

                return $array;
            },
            $this->spiedScope->getBreadcrumbs()
        );
    }

    public function getClient(): ?ClientInterface
    {
        return $this->hub->getClient();
    }

    public function getLastEventId(): ?string
    {
        throw new \RuntimeException('Not needed for test');
    }

    public function pushScope(): Scope
    {
        throw new \RuntimeException('Not needed for test');
    }

    public function popScope(): bool
    {
        throw new \RuntimeException('Not needed for test');
    }

    public function withScope(callable $callback): void
    {
        if (null !== $this->spiedScope) {
            throw new \RuntimeException('There is already a scope registered in spy');
        }

        $this->hub->withScope(function (Scope $scope) use ($callback) {
            $callback($scope);
            $this->spiedScope = $scope;
        });
    }

    public function configureScope(callable $callback): void
    {
        throw new \RuntimeException('Not needed for test');
    }

    public function bindClient(ClientInterface $client): void
    {
        throw new \RuntimeException('Not needed for test');
    }

    public function captureMessage(string $message, ?Severity $level = null): ?string
    {
        throw new \RuntimeException('Not needed for test');
    }

    public function captureException(\Throwable $exception): ?string
    {
        throw new \RuntimeException('Not needed for test');
    }

    public function captureEvent(array $payload): ?string
    {
        if (null !== $this->spiedEvent) {
            throw new \RuntimeException('There is already an event registered in spy');
        }

        $this->spiedEvent = $payload;

        return $this->hub->captureEvent($payload);
    }

    public function captureLastError(): ?string
    {
        throw new \RuntimeException('Not needed for test');
    }

    public function addBreadcrumb(Breadcrumb $breadcrumb): bool
    {
        return $this->hub->addBreadcrumb($breadcrumb);
    }

    public static function getCurrent(): HubInterface
    {
        throw new \RuntimeException('Not needed for test');
    }

    public static function setCurrent(HubInterface $hub): HubInterface
    {
        throw new \RuntimeException('Not needed for test');
    }

    public function getIntegration(string $className): ?IntegrationInterface
    {
        throw new \RuntimeException('Not needed for test');
    }
}
