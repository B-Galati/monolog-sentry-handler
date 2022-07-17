# Monolog Sentry Handler

[![Build Status](https://github.com/B-Galati/monolog-sentry-handler/workflows/CI/badge.svg?branch=main)](https://github.com/B-Galati/monolog-sentry-handler/actions?query=workflow%3ACI+branch%3Amain)
[![Latest Version](https://img.shields.io/github/release/B-Galati/monolog-sentry-handler.svg?style=flat-square)](https://packagist.org/packages/bgalati/monolog-sentry-handler)
[![MIT License](https://img.shields.io/github/license/B-Galati/monolog-sentry-handler?style=flat-square)](LICENCE)

It is a [Monolog](https://github.com/Seldaek/monolog) handler for Sentry PHP SDK v2 and v3 with breadcrumbs support.

## Features

- Send each log record to a [Sentry](https://sentry.io) server
- Send log records as breadcrumbs when they are handled in batch; the main reported log record is the one with the highest log level
- Send log along with exception when one is set in the main log record context
- Customize data sent to Sentry to fit your needs
- Compatible with Monolog 1, 2 and 3
- ~~Workaround for [an issue](https://github.com/getsentry/sentry-php/issues/811) that prevents sending logs in long running process~~

## Installation

The suggested installation method is via [composer](https://getcomposer.org/):

```bash
# For Sentry PHP SDK v3
composer require bgalati/monolog-sentry-handler

# For Sentry PHP SDK v2
composer require bgalati/monolog-sentry-handler:^1.2
```

## Basic usage

```php
<?php

use BGalati\MonologSentryHandler\SentryHandler;
use Sentry\State\Hub;

$sentryHandler = new SentryHandler(Hub::getCurrent());

/** @var $logger Monolog\Logger */
$logger->pushHandler($sentryHandler);

// Add records to the log
$logger->debug('Foo');
$logger->error('Bar');
```

Check out the [handler constructor](src/SentryHandler.php) to know how to control the minimum logging level and bubbling.

>:information_source:
>
>- It is a good idea to combine this handler with a `FingersCrossedHandler` and a `BufferHandler`
>to leverage Sentry breadcrumbs. It gives maximum context for each Sentry event and prevents slowing down http requests.
>- Beware of issue [getsentry/sentry-php#878](https://github.com/getsentry/sentry-php/issues/878) that can be solved by
>using another HTTP client
>
>Check out the symfony guide for a complete example that addresses all these points

## Documentation

- [Symfony guide](doc/guide-symfony.md): it gives a way to integrate this handler to your app
- [Extension points](doc/extension-points.md): Customize data sent to Sentry and more

## FAQ

### What are the differences with the official Monolog Sentry handler?

It is pretty much the same thing but this one captures Monolog records as breadcrumbs
when flushing in batch.

~~It provides a workaround for [issue getsentry/sentry-php#811](https://github.com/getsentry/sentry-php/issues/811) which prevents sending events to Sentry in long running process.~~

Breadcrumbs support has been proposed in a pull request that has been refused for good reasons that
can be checked in the [PR](https://github.com/getsentry/sentry-php/pull/844). Basically the official one aims to be as simple as possible.

### Why symfony guide while there is an [official Symfony bundle](https://github.com/getsentry/sentry-symfony)?

The symfony official bundle relies on Symfony [KernelException event](https://symfony.com/doc/current/reference/events.html#kernel-exception)
to send event to Sentry while Symfony already cares about logging/capturing errors thanks to Monolog bundle.

At the end, it's not possible to report silenced error with the bundle which can be problematic if you want to be aware
of problems without making your app crashed.

### What about contributing it to the Monolog project?

As per this [comment](https://github.com/Seldaek/monolog/pull/1334#issuecomment-507297849), Monolog project does
not accept new handler with 3rd party dependencies.

>For new handlers with third-party dependencies IMO the right way is to publish as a third-party package,
>with requires on monolog and on whichever dependency is needed.
>It lets Composer resolve everything which makes more sense really.

## Contributing

Fork me.

Install dependencies with `make vendor`.

Run tests with `make tests`.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Credits

- [Official Monolog handler](https://github.com/getsentry/sentry-php/blob/2.1.1/src/Monolog/Handler.php)
- [Official Laravel Monolog handler](https://github.com/getsentry/sentry-laravel/blob/1.1.0/src/Sentry/Laravel/SentryHandler.php)
