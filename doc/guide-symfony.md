# Symfony guide

> **Note** It was written for Symfony 6.1+

This guide proposed an opinionated solution to integrate Sentry in a Symfony project.

It provides the following benefits:

-   Log Monolog records of the request lifecycle (Handled message for Messenger) in Sentry breadcrumbs
-   Resolve log PSR placeholders
-   Remove deprecation logs from production logs and thus in Sentry breadcrumbs
-   Enable only high value integrations like [`RequestIntegration`](https://docs.sentry.io/platforms/php/default-integrations/#requestintegration) default;
    Thus `ExceptionListenerIntegration`, `FatalErrorListenerIntegration`, etc. are disabled as these features are already managed by Symfony.

An [implementation example](https://github.com/B-Galati/monolog-sentry-handler-example) of this guide has been created
if you want to quickly test the behavior of the handler.

## Table of contents (generated with [DocToc](https://github.com/thlorenz/doctoc))

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->

- [Step 1: Configure Sentry Bundle](#step-1-configure-sentry-bundle)
- [Step 2: Flush logs on each message handled by Symfony Messenger worker](#step-2-flush-logs-on-each-message-handled-by-symfony-messenger-worker)
- [Step 3: Filter deprecation logs](#step-3-filter-deprecation-logs)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

## Step 1: Configure Sentry Bundle

Install required libs:

```
composer require bgalati/monolog-sentry-handler sentry/sentry-symfony symfony/http-client nyholm/psr7
```

Let's configure Sentry bundle and Monolog:

```yaml
# config/packages/sentry.yaml
sentry:
    dsn: '%env(SENTRY_DSN)%'
    register_error_listener: false # Disabled to avoid duplicated Sentry events
    tracing: false
    messenger: false # Duplicates Sentry events as it is already managed through Monolog.
    options:
        attach_stacktrace: false # Disabled to avoid stacktrace on pure logs
        default_integrations: false
        integrations:
            - 'Sentry\Integration\RequestIntegration'
            - 'Sentry\Integration\EnvironmentIntegration'
            - 'Sentry\Integration\FrameContextifierIntegration'

monolog:
    handlers:
        sentry:
            type: fingers_crossed
            action_level: error
            handler: sentry_buffer
            include_stacktraces: true
            excluded_http_codes: [ 400, 401, 403, 404, 405 ]
            channels: [ "!event" ]
        sentry_buffer:
            type: buffer
            handler: sentry_handler
            level: info
            process_psr_3_messages: true
        sentry_handler:
            type: service
            id: BGalati\MonologSentryHandler\SentryHandler

services:
    _defaults:
        autowire: true
        autoconfigure: true

    BGalati\MonologSentryHandler\SentryHandler:
    Sentry\Integration\RequestIntegration:
    Sentry\Integration\EnvironmentIntegration:
    Sentry\Integration\FrameContextifierIntegration:
    Sentry\Integration\RequestFetcherInterface:
        class: Sentry\Integration\RequestFetcher
```

Let's explain the Monolog config:

-   `FingersCrossedHandler`: buffers all records until a certain level is reached
-   `BufferHandler`: keeps buffering message if the `FingersCrossedHandler` is triggered so that we have all logs for a given request
-   `SentryHandler`: sends log records in batch to Sentry

## Step 2: Flush logs on each message handled by Symfony Messenger worker

The usage of `FingersCrossedHandler` and `BufferHandler` prevents long-running process
like Symfony Messenger worker to send captured events to Sentry.

It works in an HTTP Request context because these handlers are automatically flushed
by Monolog on PHP script shutdown, but it's not how a worker works.

👉 To fix this behavior set `framework.messenger.reset_on_message` option to `true`.
_Note that this is default value of Messenger since version 6.1._

## Step 3: Filter deprecation logs

To avoid having deprecation to be logged, add this config:

```yaml
#api/config/packages/prod/framework.yaml
framework:
    php_errors:
        # @see https://symfony.com/doc/master/reference/configuration/framework.html#log
        log: 8191 # php -r "echo E_ALL & ~E_USER_DEPRECATED & ~E_DEPRECATED;"
```
