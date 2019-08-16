<?php

declare(strict_types=1);

namespace BGalati\MonologSentryHandler;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Sentry\Breadcrumb;
use Sentry\Client;
use Sentry\Severity;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use Sentry\Transport\HttpTransport;

class SentryHandler extends AbstractProcessingHandler
{
    protected $hub;
    protected $breadcrumbsBuffer = [];

    /**
     * @param HubInterface $hub    The sentry hub used to send event to Sentry
     * @param int          $level  The minimum logging level at which this handler will be triggered
     * @param bool         $bubble Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct(HubInterface $hub, int $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->hub = $hub;
    }

    /**
     * {@inheritdoc}
     */
    public function handleBatch(array $records): void
    {
        if (!$records) {
            return;
        }

        // filter records
        $records = array_filter(
            $records,
            function ($record) {
                // Keep record that matches the minimum level
                return $record['level'] >= $this->level;
            }
        );

        // the record with the highest severity is the "main" one
        $main = array_reduce(
            $records,
            static function ($highest, $record) {
                if ($record['level'] > $highest['level']) {
                    return $record;
                }

                return $highest;
            }
        );

        // the other ones are added as a context items
        foreach ($records as $record) {
            $record              = $this->processRecord($record);
            $record['formatted'] = $this->getFormatter()->format($record);

            $this->breadcrumbsBuffer[] = $record;
        }

        $this->handle($main);

        $this->breadcrumbsBuffer = [];
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record): void
    {
        $payload = [
            'level'   => $sentryLevel = $this->getSeverityFromLevel($record['level']),
            'message' => (new LineFormatter('%channel%.%level_name%: %message%'))->format($record),
        ];

        if (isset($record['context']['exception']) && $record['context']['exception'] instanceof \Throwable) {
            $payload['exception'] = $record['context']['exception'];
        }

        $this->hub->withScope(function (Scope $scope) use ($record, $payload, $sentryLevel): void {
            $scope->setLevel($sentryLevel);
            $scope->setExtra('monolog.formatted', $record['formatted'] ?? '');

            foreach ($this->breadcrumbsBuffer as $breadcrumbRecord) {
                $scope->addBreadcrumb(new Breadcrumb(
                    $this->getBreadcrumbLevelFromLevel($breadcrumbRecord['level']),
                    $this->getBreadcrumbTypeFromLevel($breadcrumbRecord['level']),
                    $breadcrumbRecord['channel'] ?? 'N/A',
                    $breadcrumbRecord['formatted'] ?? 'N/A'
                ));
            }

            $this->hub->captureEvent($payload);
        });

        $this->flushSentryEvents();
    }

    /**
     * Block until all async events are processed for the HTTP transport.
     *
     * @see https://github.com/getsentry/sentry-php/issues/811
     */
    private function flushSentryEvents(): void
    {
        // Inspired by https://github.com/getsentry/sentry-laravel/blob/14e8bf07f4254f031db3e88096ed8a8959aa34c1/src/Sentry/Laravel/Integration.php#L94-L112
        $client = $this->hub->getClient();

        if (!$client instanceof Client) {
            return;
        }

        $transportProperty = new \ReflectionProperty(Client::class, 'transport');
        $transportProperty->setAccessible(true);

        $transport = $transportProperty->getValue($client);

        if ($transport instanceof HttpTransport) {
            \Closure::bind(
                function () {$this->cleanupPendingRequests(); },
                $transport,
                $transport
            )();
        }
    }

    /**
     * Translates the Monolog level into the Sentry severity.
     *
     * @param int $level The Monolog log level
     */
    private function getSeverityFromLevel(int $level): Severity
    {
        switch ($level) {
            case Logger::DEBUG:
                return Severity::debug();
            case Logger::INFO:
            case Logger::NOTICE:
                return Severity::info();
            case Logger::WARNING:
                return Severity::warning();
            case Logger::ERROR:
                return Severity::error();
            default:
                return Severity::fatal();
        }
    }

    /**
     * Translates the Monolog level into the Sentry breadcrumb level.
     *
     * @param int $level The Monolog log level
     */
    private function getBreadcrumbLevelFromLevel(int $level): string
    {
        switch ($level) {
            case Logger::DEBUG:
                return Breadcrumb::LEVEL_DEBUG;
            case Logger::INFO:
            case Logger::NOTICE:
                return Breadcrumb::LEVEL_INFO;
            case Logger::WARNING:
                return Breadcrumb::LEVEL_WARNING;
            case Logger::ERROR:
                return Breadcrumb::LEVEL_ERROR;
            default:
                return Breadcrumb::LEVEL_CRITICAL;
        }
    }

    /**
     * Translates the Monolog level into the Sentry breadcrumb type.
     *
     * @param int $level The Monolog log level
     */
    private function getBreadcrumbTypeFromLevel(int $level): string
    {
        if ($level >= Logger::ERROR) {
            return Breadcrumb::TYPE_ERROR;
        }

        return Breadcrumb::TYPE_DEFAULT;
    }
}
