# Symfony guide

> **Note**
>
>**If you prefer something simpler** use the
>[official bundle](https://github.com/getsentry/sentry-symfony) and configure
>it to work with Monolog and `\BGalati\MonologSentryHandler\SentryHandler` handler
>as shown in this [doc](https://docs.sentry.io/platforms/php/guides/symfony/#monolog-integration).
>
>With the bundle you only need to follow steps marked with a "🎀".
>
>Example of such a config in [this PR comment](https://github.com/B-Galati/monolog-sentry-handler/pull/25#issuecomment-788855521).

> **Note**
>
> -   It was written for Symfony 6.2.
> -   Its main purpose is to give ideas of how to integrate this handler in a Symfony project

This guide proposed an opinionated solution to integrate Sentry in a Symfony project.
It uses a `FingersCrossedHandler` with a `BufferHandler` to leverage Sentry breadcrumbs
in order to give maximum context for each Sentry event. It also prevents slowing down http requests.

It provides the following benefits:

-   Flexibility: it's your code at the end so you can customize it to fit your project needs
-   Resolve issue [getsentry/sentry-php#878](https://github.com/getsentry/sentry-php/issues/878)
-   Log Monolog records of the request lifecycle (Handled message for Messenger) in Sentry breadcrumbs
-   Configure Sentry app path and excluded paths (cache and vendor)
-   Resolve log PSR placeholders
-   Capture Sentry tags:
    -   Application environment
    -   Symfony version
    -   PHP runtime / version
    -   Current route
    -   Current command (If CLI context)
    -   Current user
    -   Client IP (Resolves X-Forwarded-For)
-   Remove deprecation logs from production logs and thus in Sentry breadcrumbs
-   Enable only high value integrations like [`RequestIntegration`](https://docs.sentry.io/platforms/php/default-integrations/#requestintegration) default;
    Thus `ExceptionListenerIntegration` and `FatalErrorListenerIntegration` are disabled as these features are already managed by Symfony.

An [implementation example](https://github.com/B-Galati/monolog-sentry-handler-example) of this guide has been created
if you want to quickly test the behavior of the handler.

## Table of contents (generated with [DocToc](https://github.com/thlorenz/doctoc))

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->

-   [Step 1: Configure Sentry Hub](#step-1-configure-sentry-hub)
-   [Step 2: Configure Monolog (🎀)](#step-2-configure-monolog-)
-   [Step 3: Enrich Sentry data with Symfony events](#step-3-enrich-sentry-data-with-symfony-events)
-   [Step 4: Flush Monolog on each handled Symfony Messenger message](#step-4-flush-monolog-on-each-handled-symfony-messenger-message)
-   [Step 5: Filter deprecation logs (🎀)](#step-5-filter-deprecation-logs-)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

## Step 1: Configure Sentry Hub

Symfony http client is suggested because of its native async capabilities:

```
composer require symfony/http-client nyholm/psr7
```

Let's configure the Sentry Hub with a factory. It gives full flexibility in terms of configuration.

```php
<?php

namespace App;

use Psr\Log\LoggerInterface;
use Sentry\Integration\EnvironmentIntegration;
use Sentry\Integration\FrameContextifierIntegration;
use Sentry\Integration\RequestIntegration;
use Sentry\SentrySdk;
use Sentry\State\HubInterface;
use Sentry\State\Scope;

class SentryFactory
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function create(
        ?string $dsn,
        string $environment,
        string $release,
        string $projectRoot,
        string $cacheDir
    ): HubInterface {
        \Sentry\init([
            'dsn'                  => $dsn ?: null,
            'environment'          => $environment, // I.e.: staging, testing, production, etc.
            'in_app_include'       => [$projectRoot],
            'in_app_exclude'       => [$cacheDir, "$projectRoot/vendor"],
            'prefixes'             => [$projectRoot],
            'release'              => $release,
            'default_integrations' => false,
            'integrations'         => [
                new RequestIntegration(),
                new EnvironmentIntegration(),
                new FrameContextifierIntegration($this->logger),
            ]
        ]);

        $hub = SentrySdk::getCurrentHub();
        $hub->configureScope(static function (Scope $scope): void {
            $scope->setTags([
                'framework'       => 'symfony',
                'symfony_version' => Kernel::VERSION,
            ]);
        });

        return SentrySdk::getCurrentHub();
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
    BGalati\MonologSentryHandler\SentryHandler:

    Sentry\State\HubInterface:
        factory: ['@App\SentryFactory', 'create']
        arguments:
            $dsn: '%env(SENTRY_DSN)%'
            $environment: symfony_test_environment
            $release: 2022.07.02
            $projectRoot: '%kernel.project_dir%'
            $cacheDir: '%kernel.cache_dir%'
    # [...]
```

## Step 2: Configure Monolog (🎀)

We use the services we just declared in monolog config:

```yaml
# config/packages/prod/monolog.yaml
monolog:
    handlers:
        # [...]
        sentry:
            type: fingers_crossed
            process_psr_3_messages: true
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

-   `FingersCrossedHandler`: buffers all records until a certain level is reached
-   `BufferHandler`: keeps buffering message if the `FingersCrossedHandler` is triggered so that we have all logs for a given request
-   `SentryHandler`: sends log records in batch to Sentry

## Step 3: Enrich Sentry data with Symfony events

This listener adds context to each events captured by Sentry. A good part of what it does comes
from the official Sentry Symfony bundle actually. It registers:

-   Current user (IP, username, roles, type)
-   Response status code
-   Current route
-   Current command

```php
<?php

namespace App;

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
    public function __construct(
        private readonly HubInterface $hub,
        private readonly Security $security,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $userData['ip_address'] = $event->getRequest()->getClientIp();

        if ($user = $this->security->getUser()) {
            $userData['type']     = (new \ReflectionClass($user))->getShortName();
            $userData['username'] = $user->getUserIdentifier();
            $userData['roles']    = $user->getRoles();
        }

        $this->hub->configureScope(static function (Scope $scope) use ($userData): void {
            $scope->setUser($userData);
        });
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $matchedRoute = $event->getRequest()->attributes->get('_route');

        if ($matchedRoute === null) {
            return;
        }

        $this->hub->configureScope(static function (Scope $scope) use ($matchedRoute): void {
            $scope->setTag('route', (string) $matchedRoute);
        });
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        $statusCode = $event->getResponse()->getStatusCode();

        $this->hub->configureScope(static function (Scope $scope) use ($statusCode): void {
            $scope->setTag('status_code', (string) $statusCode);
        });
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand()?->getName() ?? 'N/A';

        $this->hub->configureScope(static function (Scope $scope) use ($command): void {
            $scope->setTag('command', $command);
        });
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
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

The usage of `FingersCrossedHandler` and `BufferHandler` prevents long-running process
like Symfony Messenger worker to send captured events to Sentry.

It works in an HTTP Request context because these handlers are automatically flushed
by Monolog on PHP script shutdown, but it's not how a worker works.

👉 To fix this behavior set `framework.messenger.reset_on_message` option to `true`.
_Note that this is default value of Messenger since version 6.1._

## Step 5: Filter deprecation logs (🎀)

To avoid having deprecation to be logged, add this config:

```yaml
#api/config/packages/prod/framework.yaml
framework:
    php_errors:
        # @see https://symfony.com/doc/master/reference/configuration/framework.html#log
        log: 8191 # php -r "echo E_ALL & ~E_USER_DEPRECATED & ~E_DEPRECATED;"
```
