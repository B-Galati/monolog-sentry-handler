<?php

declare(strict_types=1);

namespace Homeapp\MonologSentryHandler;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Sentry\Breadcrumb;
use Sentry\ClientInterface;
use Sentry\Event as SentryEvent;
use Sentry\ExceptionDataBag;
use Sentry\Severity;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use Throwable;

class SentryHandler extends AbstractProcessingHandler
{
    protected HubInterface $hub;

    private array $breadcrumbsBuffer = [];

    private bool $sendContext;

    /**
     * @param HubInterface $hub         The sentry hub used to send event to Sentry
     * @param int          $level       The minimum logging level at which this handler will be triggered
     * @param bool         $bubble      Whether the messages that are handled can bubble up the stack or not
     * @param bool         $sendContext Whether to send record['context'] vars to sentry
     */
    public function __construct(
        HubInterface $hub,
        int $level = Logger::DEBUG,
        bool $bubble = true,
        bool $sendContext = true
    ) {
        parent::__construct($level, $bubble);

        $this->hub         = $hub;
        $this->sendContext = $sendContext;
    }

    /**
     * {@inheritdoc}
     */
    public function handleBatch(array $records): void
    {
        $records = array_filter(
            $records,
            fn ($record) => $record['level'] >= $this->level
        );

        if (empty($records)) {
            return;
        }

        // the record with the highest severity is the "main" one
        $main = array_reduce(
            $records,
            static function ($highest, $record) {
                return $record['level'] >= ($highest['level'] ?? $record['level']) ? $record : $highest;
            }
        );

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
        $sentryEvent = SentryEvent::createEvent();
        $sentryLevel = $this->getSeverityFromLevel((int) $record['level']);
        $sentryEvent->setLevel($sentryLevel);
        $sentryEvent->setMessage((new LineFormatter('%channel%.%level_name%: %message%'))->format($record));

        if (isset($record['context']['exception']) && $record['context']['exception'] instanceof Throwable) {
            $sentryEvent->setExceptions([new ExceptionDataBag($record['context']['exception'])]);
        }

        $this->hub->withScope(
            function (Scope $scope) use ($record, $sentryEvent, $sentryLevel): void {
                $scope->setLevel($sentryLevel);
                $scope->setExtra('monolog.formatted', $record['formatted'] ?? '');
                if ($this->sendContext) {
                    $this->fillScopeWithContext($record, $scope);
                }

                $this->fillContextFromBreadcrumbs($scope);

                $this->processScope($scope, $record, $sentryEvent);

                $this->hub->captureEvent($sentryEvent);
            });

        $this->afterWrite();
    }

    private function fillScopeWithContext(array $record, Scope $scope): void
    {
        if (isset($record['context']) && \is_array($record['context'])) {
            foreach ($record['context'] as $key => $value) {
                if (is_scalar($value)) {
                    $scope->setExtra((string) $key, (string) $value);
                }
            }
        }
    }

    private function fillContextFromBreadcrumbs(Scope $scope): void
    {
        foreach ($this->breadcrumbsBuffer as $breadcrumbRecord) {
            $context = array_merge($breadcrumbRecord['context'], $breadcrumbRecord['extra']);

            $scope->addBreadcrumb(
                new Breadcrumb(
                    $this->getBreadcrumbLevelFromLevel((int) $breadcrumbRecord['level']),
                    $this->getBreadcrumbTypeFromLevel((int) $breadcrumbRecord['level']),
                    (string) $breadcrumbRecord['channel'] ?: 'N/A',
                    (string) $breadcrumbRecord['message'] ?: 'N/A',
                    $context
                )
            );
        }
    }

    /**
     * @todo: Allow such extensions via injecting processors for scope, not using inheritance (or just delete this method)
     * Extension point.
     *
     * This method is called when Sentry event is captured by the handler.
     * Override it if you want to add custom data to Sentry $scope.
     *
     * @param Scope       $scope       Sentry scope where you can add custom data
     * @param array       $record      Current monolog record
     * @param SentryEvent $sentryEvent Current sentry event that will be captured
     */
    private function processScope(Scope $scope, array $record, SentryEvent $sentryEvent): void
    {
    }

    /**
     * @todo: Allow such extensions via injecting processors for scope, not using inheritance (or just delete this method)
     * Extension point.
     *
     * Overridable method that for example can be used to:
     *   - disable Sentry event flush
     *   - add some custom logic after monolog write process
     *   - ...
     */
    private function afterWrite(): void
    {
        $client = $this->hub->getClient();

        if ($client instanceof ClientInterface) {
            $client->flush();
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
                return Breadcrumb::LEVEL_FATAL;
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
