---
paths:
  - 'app/**|resources/**|routes/**|tests/**|docs/parity/**'
---

# Parity

## Preserve complete legacy feature parity
Every capability classified in docs/parity/features.md is required before production cutover. Follow approved priorities in P0, P1, P2, then P3 order; `OPS-03` and `ANALYTICS-01` remain required but their proposed priorities require Charlie Langridge's approval before entering that queue. Start the final tranche—direct-debit migration, PayPal, CCTV, Discord, API documentation, and secure log viewing—only after every other required capability is accepted, and complete it before cutover. Preserve outcomes and external contracts with supported replacements; obsolete packages and unsafe protocols are not parity requirements.
