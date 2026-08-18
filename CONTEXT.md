# BB Membership System

The shared language for rebuilding the legacy membership system while preserving its operational behaviour.

## Language

**Feature parity**:
Every capability classified in the parity ledger has its business outcome or external contract available in the replacement application before production cutover. It does not require preserving obsolete packages, unsafe protocols, or the legacy interface.
_Avoid_: Code parity, package parity

**Production cutover**:
The controlled event that makes the Laravel 13 application the sole production application and business-data writer after all parity and release gates pass.
_Avoid_: Partial launch, incremental cutover

**Final parity tranche**:
The required capabilities deliberately implemented after every other parity capability but still before production cutover: historical direct-debit migration, PayPal payments/donations, CCTV capture, Discord status notifications, API documentation, and secure log viewing.
_Avoid_: Optional backlog, post-launch features

**Payment**:
A recorded movement or attempted movement of money, with a settlement lifecycle independent of what the money is intended to satisfy.
_Avoid_: Charge, amount owed

**Subscription charge**:
An amount a member owes for a membership period. One or more payment events may attempt to settle it.
_Avoid_: Payment, provider transaction

**Equipment session**:
A recorded interval in which a member uses a device or piece of equipment, later classified for exclusion or billing.
_Avoid_: Equipment payment, access attempt

**Access attempt**:
A request to enter the space or use controlled equipment, together with the resulting access decision. It is distinct from an equipment session, which records actual usage over time.
_Avoid_: Equipment session, heartbeat
