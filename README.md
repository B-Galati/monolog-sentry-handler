# Monolog Sentry Handler

[![Build Status](https://img.shields.io/travis/B-Galati/monolog-sentry-handler/master.svg?style=flat-square)](https://travis-ci.org/B-Galati/monolog-sentry-handler)
[![Latest Version](https://img.shields.io/github/release/B-Galati/monolog-sentry-handler.svg?style=flat-square)](https://packagist.org/packages/bgalati/monolog-sentry-handler)
[![MIT License](https://img.shields.io/github/license/B-Galati/monolog-sentry-handler?style=flat-square)](LICENCE)

## Features

- Send each log record to a [Sentry](https://sentry.io) server
- Send log records as breadcrumbs when they are handled in batch; the main reported log record is the one with the highest log level
- Send log along with exception when one is set in the main log record context 
- Workaround for [an issue](https://github.com/getsentry/sentry-php/issues/811) that prevents sending logs in long running process

## Requirements

- PHP 7.1+
- [Sentry PHP SDK v2](https://github.com/getsentry/sentry-php) 

## Installation

The suggested installation method is via [composer](https://getcomposer.org/):

```bash
composer require bgalati/monolog-sentry-handler
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

Checkout the [handler constructor](src/SentryHandler.php) to know how to control the minimum logging level and bubbling.

## Documentation

- [Symfony guide](doc/guide-symfony.md): it gives a way to integrate this handler to your app

## FAQ

### What are the differences with the official Monolog Sentry handler?

It is pretty much the same thing but this one capture Monolog records as breadcrumbs 
when flushing in batch.

It provides a workaround for [issue 811](https://github.com/getsentry/sentry-php/issues/811).

Breadcrumbs support has been proposed in a pull request that has been refused for good reasons that
can be checked in the [PR](https://github.com/getsentry/sentry-php/pull/844). Basically the official one aims to be as simple as possible. 

### Why symfony guide while there is an [official Symfony bundle](https://github.com/getsentry/sentry-symfony)?

The symfony official bundle relies on Symfony [KernelException event](https://symfony.com/doc/current/reference/events.html#kernel-exception) 
to send event to Sentry while Symfony already cares about logging/capturing errors thanks the Monolog bundle.

At the end, it's not possible to report silenced error with the bundle which can be problematic if you want to be aware 
of problems without making your app crashed.

## Contributing

Clone the repository:

```bash
git clone git@github.com:B-Galati/monolog-sentry-handler.git
``` 

Install dependencies with `make vendor`.

Tests can be ran with `make tests`.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Credits

- [Official Monolog handler](https://github.com/getsentry/sentry-php/blob/2.1.1/src/Monolog/Handler.php)
- [Official Laravel Monolog handler](https://github.com/getsentry/sentry-laravel/blob/1.1.0/src/Sentry/Laravel/SentryHandler.php)
