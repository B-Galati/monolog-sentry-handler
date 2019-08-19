# Symfony guide

>ℹ️
>
>- It was written for Symfony 4.
>- Its main purpose is to give ideas of how to integrate this handler in a Symfony project

This guide proposed an opinionated solution to integrate Sentry in a Symfony project.
It uses a `FingersCrossedHandler` with a `BufferHandler` to leverage Sentry breadcrumbs 
in order to give maximum context for each Sentry event.

It provides the following benefits:

- Flexibility: it's your code at the end so you can customize it to fit your project needs
- Log Monolog records of the request lifecycle (Handled message for Messenger) in Sentry breadcrumbs
- Configure Sentry app path and excluded paths (cache and vendor)
- Resolve log PSR placeholders
- Capture Sentry tags:
  - Application environment
  - Symfony version
  - PHP runtime / version
  - Current route
  - Current command (If CLI context)
  - Current user
  - Client IP (Resolves X-Forwarded-For)
- Enable only [`RequestIntegration`](https://docs.sentry.io/platforms/php/default-integrations/#requestintegration) default integration;
  Thus `ExceptionListenerIntegration` and `FatalErrorListenerIntegration` are disabled as these features are already managed by Symfony.

## Table of contents (generated with [DocToc](https://github.com/thlorenz/doctoc))

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->


- [Step 1: Configure Sentry Hub](#step-1-configure-sentry-hub)
- [Step 2: Configure Monolog](#step-2-configure-monolog)
- [Step 3: Enrich Sentry data with Symfony events](#step-3-enrich-sentry-data-with-symfony-events)
- [Step 4: Flush Monolog on each handled Symfony Messenger message](#step-4-flush-monolog-on-each-handled-symfony-messenger-message)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

## Step 1: Configure Sentry Hub

Let's configure the Sentry Hub with a factory. It gives full flexibility in terms of configuration.

```php
<?php

use App\Kernel;
use Sentry\ClientBuilder;
use Sentry\Integration\RequestIntegration;
use Sentry\State\Hub;
use Sentry\State\HubInterface;

class SentryFactory
{
    public function create(
        ?string $dsn,
        string $environment,
        string $release,
        string $projectRoot,
        string $cacheDir
    ): HubInterface {
        $clientBuilder = ClientBuilder::create([
            'dsn'                  => $dsn,
            'environment'          => $environment, // I.e.: staging, testing, production, etc.
            'project_root'         => $projectRoot,
            'in_app_exclude'       => [$cacheDir, "$projectRoot/vendor"],
            'prefixes'             => [$projectRoot],
            'release'              => $release,
            'default_integrations' => false,
            'tags'                 => [
                'php_uname'       => \PHP_OS,
                'php_sapi_name'   => \PHP_SAPI,
                'php_version'     => \PHP_VERSION,
                'framework'       => 'symfony',
                'symfony_version' => Kernel::VERSION,
            ],
        ]);

        // Enable Sentry RequestIntegration
        $options = $clientBuilder->getOptions();
        $options->setIntegrations([new RequestIntegration($options)]);

        $client = $clientBuilder->getClient();

        // A global HubInterface must be set otherwise some feature provided by the SDK does not work as they rely on this global state
        return Hub::setCurrent(new Hub($client));
    }
}
```

Then the factory is registered in the Symfony container:

```yaml
# config/services.yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true
  # [...]
  BGalati\MonologSentryHandler\SentryHandler: ~

  Sentry\State\HubInterface:
    factory: 'App\Common\Infrastructure\SentryFactory:create'
    arguments:
      $dsn: '%env(SENTRY_DSN)%'
      $environment: '%env(ENVIRONMENT)%'
      $release: '%env(APP_VERSION)%'
      $projectRoot: '%kernel.project_dir%'
      $cacheDir: '%kernel.cache_dir%'
  
  # Resolve log PSR placeholders, it can be useful to make breadcrumbs easier to read 
  Monolog\Processor\PsrLogMessageProcessor:
    tags: [monolog.processor]
  # [...]  
```

## Step 2: Configure Monolog

We use the services we just declared in monolog config:

```yaml
# config/packages/prod/monolog.yaml
monolog:
  handlers:
    # [...]
    sentry:
      type: fingers_crossed
      action_level: warning
      handler: sentry_buffer
      excluded_http_codes: [400, 401, 403, 404, 405]
      buffer_size: 100 # Prevents memory leaks for workers
      channels: ["!event", "!security"]
    sentry_buffer:
      type: buffer
      handler: sentry_handler
    sentry_handler:
      type: service
      id: 'BGalati\MonologSentryHandler\SentryHandler'
    # [...]
```

Let's explain what all of these is doing: 

- `FingersCrossedHandler`: buffers all records until a certain level is reached
- `BufferHandler`: keeps buffering message if the `FingersCrossedHandler` is triggered so that we have all logs for a given request
- `SentryHandler`: sends log records in batch to Sentry

## Step 3: Enrich Sentry data with Symfony events

This listener adds context to each events captured by Sentry. A good part of what it does comes
from the official Sentry Symfony bundle actually. It registers:

- Current user (IP, username, roles, type)
- Response status code
- Current route
- Current command

```php
<?php

use Psr\Log\LoggerInterface;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;

class SentryListener implements EventSubscriberInterface
{
    private $hub;
    private $security;
    private $logger;

    public function __construct(HubInterface $hub, Security $security, LoggerInterface $logger)
    {
        $this->hub      = $hub;
        $this->security = $security;
        $this->logger   = $logger;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $userData['ip_address'] = $event->getRequest()->getClientIp();

        if ($user = $this->security->getUser()) {
            $userData['type']     = (new \ReflectionClass($user))->getShortName();
            $userData['username'] = $user->getUsername();
            $userData['roles']    = $user->getRoles();
        }

        $this->hub->configureScope(
            static function (Scope $scope) use ($userData): void {
                $scope->setUser($userData);
            }
        );
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if (!$event->getRequest()->attributes->has('_route')) {
            return;
        }

        $matchedRoute = (string) $event->getRequest()->attributes->get('_route');

        $this->hub->configureScope(
            static function (Scope $scope) use ($matchedRoute): void {
                $scope->setTag('route', $matchedRoute);
            }
        );
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        $statusCode = $event->getResponse()->getStatusCode();

        $this->hub->configureScope(
            static function (Scope $scope) use ($statusCode): void {
                $scope->setTag('status_code', (string) $statusCode);
            }
        );

        if ($statusCode >= 500) {
            // 5XX response are private/security data safe so let's log them for debugging purpose
            $this->logger->error('500 returned', ['response' => $event->getResponse()]);
        }
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        $this->hub
            ->configureScope(static function (Scope $scope) use ($command): void {
                $scope->setTag('command', $command ? $command->getName() : 'N/A');
            })
        ;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST    => ['onKernelRequest', 1],
            KernelEvents::CONTROLLER => ['onKernelController', 10000],
            KernelEvents::TERMINATE  => ['onKernelTerminate', 1],
            ConsoleEvents::COMMAND   => ['onConsoleCommand', 1],
        ];
    }
}
```

## Step 4: Flush Monolog on each handled Symfony Messenger message

The usage of `FingersCrossedHandler` and `BufferHandler` prevents long running process
like Symfony Messenger worker to send captured events to Sentry.

It works in a HTTP Request context because these handlers are automatically flushed
by Monolog on PHP script shutdown but it's not how a worker works.

The listener below resolves this problem by manually flushed Monolog logger 
on `WorkerMessageFailedEvent` and `WorkerMessageHandledEvent` events.

```php
<?php

use Monolog\ResettableInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Webmozart\Assert\Assert;

class MonologResetterEventListener implements EventSubscriberInterface
{
    /** @var LoggerInterface&ResettableInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        Assert::isInstanceOf($logger, ResettableInterface::class);
        $this->logger = $logger;
    }

    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $message  = $event->getEnvelope()->getMessage();

        $context = [
            'message'   => $message,
            'error'     => $event->getThrowable()->getMessage(),
            'class'     => \get_class($message),
            'exception' => $event->getThrowable(),
        ];

        $this->logger->error('Error thrown while handling message {class}. Error: "{error}"', $context);

        $this->resetLogger();
    }

    public function onMessageHandled(WorkerMessageHandledEvent $event): void
    {
        $this->resetLogger();
    }

    public function resetLogger(): void
    {
        $this->logger->reset();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            // It should be called after \Symfony\Component\Messenger\EventListener\SendFailedMessageToFailureTransportListener
            // So that we have as much information as we can
            WorkerMessageFailedEvent::class  => ['onMessageFailed', -200],
            WorkerMessageHandledEvent::class => 'onMessageHandled',
        ];
    }
}
```
