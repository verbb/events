# Capacity

Events supports capacity limits at three levels: ticket type, session, and event. Each limit is optional, and a blank capacity means that level does not add a limit.

When a ticket is checked for availability, Events uses the most restrictive remaining limit:

```
min(ticket type remaining, session remaining if set, event remaining if set)
```

For example, if a ticket type has 12 seats remaining, its session has 8 seats remaining, and the event has 5 seats remaining, the ticket has 5 seats available.

Purchased counts only include **active** purchased tickets. Cancelled reservations are kept for reporting, but no longer consume capacity.

## Ticket Type Capacity

Ticket type capacity limits how many tickets of that type can be sold. If the capacity is blank, that ticket type is treated as unlimited unless a session or event capacity also applies.

Purchased counts for this limit are scoped to the generated ticket, which represents a specific session and ticket type combination.

## Session Capacity

Session capacity is a shared pool across all ticket types for a single session. Use this when each class, course date, performance, or session has its own attendee limit.

If the session capacity is blank, the session does not add a capacity limit and availability falls back to ticket type capacity, plus event capacity if set.

## Event Capacity

Event capacity is a shared pool across the whole event. This includes all sessions and all ticket types.

For multi-session events, leave event capacity blank unless every session should draw from the same event-wide pool. For example, an event capacity of 20 means 20 seats total across the event, not 20 seats per session.

## Auto and Zero Values

Blank capacity values are treated as auto/no limit for that level.

A value of `0` is treated as an explicit zero-capacity limit. If you upgraded from an older version where `0` was used as a workaround for blank event capacity, convert those values to `NULL` before relying on the newer capacity calculations:

```sql
UPDATE events_events SET capacity = NULL WHERE capacity = 0;
```

## Releasing Capacity

When an order is cancelled or refunded, its purchased tickets should be **cancelled** so they no longer count toward capacity.

### Cancelling purchased tickets

Use the **Cancel tickets** action in **Events → Purchased Tickets**. Cancelled tickets:

- Stop counting toward ticket type, session, and event capacity
- Remain visible for reporting and audit history
- Cannot be checked in

Use **Restore tickets** to make a cancelled reservation active again, as long as the related order has not been moved to a release-capacity status.

### Order status changes

When a Commerce order moves to one of the configured **Release Capacity Order Statuses** (Settings → Events → Tickets), Events automatically cancels all active purchased tickets for that order.

The default handles are `cancelled`, `canceled`, and `refunded`. Update these to match your store’s order statuses.

### Refunds

When **Cancel Purchased Tickets on Refund** is enabled (Settings → Events → Tickets), Events cancels purchased tickets automatically when Commerce refunds are processed.

For orders with a single ticket line item, Events cancels tickets based on the refunded amount. For full transaction refunds on orders with multiple ticket line items, all active purchased tickets are cancelled.

### Deleting purchased tickets

Deleting a purchased ticket is separate from cancellation. Use deletion only to remove mistaken records or for GDPR cleanup. Cancelled tickets can be permanently deleted from the trash, or with:

```bash
./craft events/purchased-tickets/purge-trashed --dry-run=0
```

Trashed records can also be purged automatically after a retention period via **Trashed Purchased Ticket Retention** in plugin settings.
