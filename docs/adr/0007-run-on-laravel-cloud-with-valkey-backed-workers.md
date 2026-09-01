# Run on Laravel Cloud with Valkey-backed workers

Production will run on Laravel Cloud with PHP 8.4, Node 24 builds, managed MySQL 8.4, and Laravel Valkey for cache, encrypted sessions, and the Redis queue driver. Queue work runs outside web requests on dedicated Cloud worker compute; Laravel Cloud managed queues may replace that backend later only after their delivery semantics, dependency, and cost are approved.
