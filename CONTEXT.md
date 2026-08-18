# BB Membership System

The shared language for rebuilding the legacy membership system while preserving its operational behaviour.

## Language

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
