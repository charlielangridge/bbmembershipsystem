---
paths:
  - 'app/**|config/**|resources/**|routes/**|tests/**|docs/parity/**'
---

# Parity

## Use Flare and anonymous Fathom analytics
Use Flare for P1 production error reporting and remove Rollbar rather than migrating it. Use Fathom for P2 anonymous aggregate page views across public, member, and administrative browser pages; never send member identifiers or sensitive business data, and do not add marketing attribution or custom journey events unless separately approved.

## Preserve complete legacy feature parity
Every capability classified in docs/parity/features.md is required before production cutover. Follow approved priorities in P0, P1, P2, then P3 order. Start the final tranche—direct-debit migration, PayPal, CCTV, Discord, API documentation, and secure log viewing—only after every other required capability is accepted, and complete it before cutover. Preserve outcomes and external contracts with supported replacements; obsolete packages and unsafe protocols are not parity requirements.

## Use Reverb for realtime broadcasting
Replace the legacy hosted Pusher service and custom authorization endpoint with Laravel Reverb, Laravel Echo, and standard private-channel authorization. Preserve required member-notification and activity outcomes, but do not retain hard-coded keys or publicly expose member activity data without an approved privacy contract.
