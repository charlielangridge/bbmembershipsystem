# Isolate the rebuild from legacy production

The Laravel 13 rebuild is developed as tested vertical slices on `modernisation/laravel-13`, with small reviewable commits and CI required before promotion. The legacy hosted application remains untouched until an approved cutover because coupling rebuild work to the fragile production checkout would make rollback and behavioural comparison unsafe.
