# UPGRADE FROM 1.x to 2.x

-   PHP minimal version bumped to 7.4
-   Sentry SDK bumped to V3
-   Re-read [the Symfony guide](doc/guide-symfony.md) to update your integration
-   Method `SentryHandler::processScope()` signature updated, so if you extend `SentryHandler` you will need to update the method
