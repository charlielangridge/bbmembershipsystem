# Report errors to Flare without member identity

Production exceptions are reported through `spatie/laravel-flare`, replacing Rollbar. Reporting is disabled without an explicit environment key, and reports censor credentials, IP addresses, cookies, sessions, and authenticated member identity so operational diagnosis does not become an unnecessary member-data feed.
