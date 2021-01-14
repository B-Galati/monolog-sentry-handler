<?php

declare(strict_types=1);

namespace BGalati\MonologSentryHandler;

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
    /**
     * @var HubInterface
     */
    protected $hub;

    /**
     * @var array
     */
    private $breadcrumbsBuffer = [];

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

        $records = array_filter(
            $records,
            function ($record) {
                // Keep record that matches the minimum level
                return $record['level'] >= $this->level;
            }
        );

        if (!$records) {
            return;
        }

        // the record with the highest severity is the "main" one
        $main = array_reduce(
            $records,
            static function ($highest, $record) {
                if (null === $highest || $record['level'] > $highest['level']) {
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
        $sentryEvent = SentryEvent::createEvent();
        $sentryEvent->setLevel($sentryLevel = $this->getSeverityFromLevel((int) $record['level']));
        $sentryEvent->setMessage((new LineFormatter('%channel%.%level_name%: %message%'))->format($record));

        if (isset($record['context']['exception']) && $record['context']['exception'] instanceof Throwable) {
            $sentryEvent->setExceptions([new ExceptionDataBag($record['context']['exception'])]);
        }

        $this->hub->withScope(
            function (Scope $scope) use ($record, $sentryEvent, $sentryLevel): void {
                $scope->setLevel($sentryLevel);
                $scope->setExtra('monolog.formatted', $record['formatted'] ?? '');

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

                $this->processScope($scope, $record, $sentryEvent);

                $this->hub->captureEvent($sentryEvent);
            });

        $this->afterWrite();
    }

    /**
     * Extension point.
     *
     * This method is called when Sentry event is captured by the handler.
     * Override it if you want to add custom data to Sentry $scope.
     *
     * @param Scope       $scope       Sentry scope where you can add custom data
     * @param array       $record      Current monolog record
     * @param SentryEvent $sentryEvent Current sentry event that will be captured
     */
    protected function processScope(Scope $scope, array $record, SentryEvent $sentryEvent): void
    {
    }

    /**
     * Extension point.
     *
     * Overridable method that for example can be used to:
     *   - disable Sentry event flush
     *   - add some custom logic after monolog write process
     *   - ...
     */
    protected function afterWrite(): void
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
