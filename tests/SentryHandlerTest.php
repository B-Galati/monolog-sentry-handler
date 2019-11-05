<?php

declare(strict_types=1);

namespace BGalati\MonologSentryHandler\Tests;

use BGalati\MonologSentryHandler\SentryHandler;
use Coduo\PHPMatcher\PHPUnit\PHPMatcherAssertions;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Sentry\Breadcrumb;
use Sentry\ClientBuilder;
use Sentry\Event;
use Sentry\Severity;
use Sentry\State\Hub;
use Sentry\State\Scope;
use Sentry\Transport\ClosableTransportInterface;
use Sentry\Transport\NullTransport;

class SentryHandlerTest extends TestCase
{
    use PHPMatcherAssertions;

    private $hub;
    private $transport;

    protected function setUp(): void
    {
        $this->transport = new SpyTransport();

        $clientBuilder = ClientBuilder::create(['default_integrations' => false]);
        $clientBuilder->setTransport($this->transport);

        $client = $clientBuilder->getClient();

        $this->hub = new Hub($client);
    }

    protected function tearDown(): void
    {
        $this->hub       = null;
        $this->transport = null;
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

        $this->assertTrue($handler->afterWriteCalled);
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

        $this->assertTrue($handler->afterWriteCalled);
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

        $this->assertFalse($handler->afterWriteCalled);
        $this->assertNull($this->transport->spiedEvent);
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

        $this->assertTrue($handler->afterWriteCalled);
        $this->assertCapturedEvent(
            Severity::fatal(),
            'chan-emerg.EMERGENCY: Emergency message',
            ['monolog.formatted' => 'chan-emerg.EMERGENCY: Emergency message ["extra-emerg"]'],
            null,
            [
                [
                    'type'      => 'default',
                    'category'  => 'chan-info',
                    'level'     => 'info',
                    'message'   => 'chan-info.INFO: Info message ["extra-info"]',
                    'timestamp' => '@double@',
                    'data'      => [],
                ],
                [
                    'type'      => 'error',
                    'category'  => 'chan-error',
                    'level'     => 'error',
                    'message'   => 'chan-error.ERROR: Error Message ["extra-error"]',
                    'timestamp' => '@double@',
                    'data'      => [],
                ],
                [
                    'type'      => 'default',
                    'category'  => 'chan-debug',
                    'level'     => 'debug',
                    'message'   => 'chan-debug.DEBUG: Debug message ["extra-debug"]',
                    'timestamp' => '@double@',
                    'data'      => [],
                ],
                [
                    'type'      => 'error',
                    'category'  => 'chan-emerg',
                    'level'     => 'fatal',
                    'message'   => 'chan-emerg.EMERGENCY: Emergency message ["extra-emerg"]',
                    'timestamp' => '@double@',
                    'data'      => [],
                ],
                [
                    'type'      => 'default',
                    'category'  => 'chan-warn',
                    'level'     => 'warning',
                    'message'   => 'chan-warn.WARNING: Warning message ["extra-warn"]',
                    'timestamp' => '@double@',
                    'data'      => [],
                ],
                [
                    'type'      => 'default',
                    'category'  => 'chan-notice',
                    'level'     => 'info',
                    'message'   => 'chan-notice.NOTICE: Notice message ["extra-notice"]',
                    'timestamp' => '@double@',
                    'data'      => [],
                ],
                [
                    'type'      => 'error',
                    'category'  => 'chan-alert',
                    'level'     => 'fatal',
                    'message'   => 'chan-alert.ALERT: Alert message ["extra-alert"]',
                    'timestamp' => '@double@',
                    'data'      => [],
                ],
                [
                    'type'      => 'error',
                    'category'  => 'chan-critical',
                    'level'     => 'fatal',
                    'message'   => 'chan-critical.CRITICAL: Critical message ["extra-critical"]',
                    'timestamp' => '@double@',
                    'data'      => [],
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

        $this->assertTrue($handler->afterWriteCalled);
        $this->assertCapturedEvent(
            Severity::fatal(),
            'test.CRITICAL: Critical message',
            ['monolog.formatted' => 'test.CRITICAL: Critical message []'],
            $exception,
            [
                [
                    'type'      => 'error',
                    'category'  => 'test',
                    'level'     => 'error',
                    'message'   => 'test.ERROR: Error Message []',
                    'timestamp' => '@double@',
                    'data'      => [],
                ],
                [
                    'type'      => 'default',
                    'category'  => 'test',
                    'level'     => 'warning',
                    'message'   => 'test.WARNING: Warning message []',
                    'timestamp' => '@double@',
                    'data'      => [],
                ],
                [
                    'type'      => 'error',
                    'category'  => 'test',
                    'level'     => 'fatal',
                    'message'   => 'test.CRITICAL: Critical message []',
                    'timestamp' => '@double@',
                    'data'      => [],
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

        $this->assertTrue($handler->afterWriteCalled);

        $handler->resetSpy();
        $this->transport->resetSpy();

        $handler->handleBatch($records);

        $this->assertTrue($handler->afterWriteCalled);
        $this->assertCapturedEvent(
            Severity::info(),
            'test.INFO: Info message',
            ['monolog.formatted' => 'test.INFO: Info message []'],
            null,
            [
                [
                    'type'      => 'default',
                    'category'  => 'test',
                    'level'     => 'info',
                    'message'   => 'test.INFO: Info message []',
                    'timestamp' => '@double@',
                    'data'      => [],
                ],
            ]
        );
    }

    private function assertCapturedEvent(Severity $severity, string $message, array $extra, \Exception $exception = null, array $breadcrumbs = []): void
    {
        $event = $this->transport->spiedEvent->toArray();

        if (null !== $exception) {
            $this->assertCount(1, $event['exception']['values']);
            $this->assertSame(\get_class($exception), $event['exception']['values'][0]['type']);
            $this->assertSame($exception->getMessage(), $event['exception']['values'][0]['value']);
        } else {
            $this->assertArrayNotHasKey('exception', $event);
        }

        $this->assertTrue($this->transport->isFlushed);
        $this->assertArrayNotHasKey('tags', $event);
        $this->assertArrayNotHasKey('user', $event);
        $this->assertSame($message, $event['message']);
        $this->assertSame((string) $severity, $event['level']);
        $this->assertMatchesPattern('@string@', $event['event_id']);
        $this->assertMatchesPattern('@string@', $event['timestamp']);
        $this->assertMatchesPattern('@string@', $event['platform']);
        $this->assertMatchesPattern('@string@', $event['server_name']);
        $this->assertMatchesPattern(['processScope' => 'called'] + $extra, $event['extra']);

        if ($breadcrumbs) {
            $this->assertMatchesPattern(
                json_encode($breadcrumbs),
                json_encode($event['breadcrumbs']['values'])
            );
        } else {
            $this->assertArrayNotHasKey('breadcrumbs', $event);
        }

        $this->assertMatchesPattern(
            [
                'name'    => 'sentry.php',
                'version' => '@string@',
            ],
            $event['sdk']
        );

        $this->assertMatchesPattern(
            [
                'os' => [
                    'name'           => '@string@',
                    'version'        => '@string@',
                    'build'          => '@string@',
                    'kernel_version' => '@string@',
                ],
                'runtime' => [
                    'name'    => 'php',
                    'version' => '@string@',
                ],
            ],
            $event['contexts']
        );
    }

    private function createSentryHandler(int $level = null): SpySentryHandler
    {
        if (null === $level) {
            $handler = new SpySentryHandler($this->hub);
        } else {
            $handler = new SpySentryHandler($this->hub, $level);
        }

        $handler->setFormatter(new LineFormatter('%channel%.%level_name%: %message% %extra%'));

        return $handler;
    }
}

class SpySentryHandler extends SentryHandler
{
    public $afterWriteCalled = false;

    /** {@inheritdoc} */
    protected function processScope(Scope $scope, array $record, array $sentryEvent): void
    {
        $scope->setExtra('processScope', 'called');
    }

    /** {@inheritdoc} */
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

class SpyTransport extends NullTransport implements ClosableTransportInterface
{
    /**
     * @var Event|null
     */
    public $spiedEvent;

    /**
     * @var bool
     */
    public $isFlushed = false;

    public function send(Event $event): ?string
    {
        $this->spiedEvent = $event;

        return parent::send($event);
    }

    public function resetSpy(): void
    {
        $this->spiedEvent = null;
        $this->isFlushed  = false;
    }

    public function getBreadcrumbsAsArray(): array
    {
        if (null === $this->spiedEvent) {
            throw new \RuntimeException('No spied scope');
        }

        return array_map(
            function (Breadcrumb $breadcrumb) {
                $array = $breadcrumb->toArray();

                unset($array['timestamp']);

                return $array;
            },
            $this->spiedEvent->getBreadcrumbs()
        );
    }

    public function close(?int $timeout = null): PromiseInterface
    {
        $this->isFlushed = true;

        return new FulfilledPromise(true);
    }
}
