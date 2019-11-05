# Extension points

It is required to inherit from `SentryHandler` class and override these methods:

```php
<?php

use BGalati\MonologSentryHandler\SentryHandler;
use Sentry\State\Scope;

class CustomSentryHandler extends SentryHandler
{
    /** {@inheritdoc} */
    protected function processScope(Scope $scope, array $record, array $sentryEvent): void
    {
        // Your custom logic like this one:
        // ....
        if (isset($record['context']['extra']) && \is_array($record['context']['extra'])) {
            foreach ($record['context']['extra'] as $key => $value) {
                $scope->setExtra((string) $key, $value);
            }
        }

        if (isset($record['context']['tags']) && \is_array($record['context']['tags'])) {
            foreach ($record['context']['tags'] as $key => $value) {
                $scope->setTag($key, $value);
            }
        }
    }

    /** {@inheritdoc} */
    protected function afterWrite(): void
    {
        // Your custom code before events are flushed
        // ...

        // Call parent method to keep default behavior or don't call it if you don't need it
        parent::afterWrite();
    }
}
```

Please look at these methods within [the code](../src/SentryHandler.php) if you want more details.
