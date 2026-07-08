# Capacity

Events supports capacity limits at three levels: ticket type, session, and event. Each limit is optional, and a blank capacity means that level does not add a limit.

When a ticket is checked for availability, Events uses the most restrictive remaining limit:

```
min(ticket type remaining, session remaining if set, event remaining if set)
```

For example, if a ticket type has 12 seats remaining, its session has 8 seats remaining, and the event has 5 seats remaining, the ticket has 5 seats available.

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

When an order is cancelled or refunded, you need to remove its purchased tickets so they no longer count toward capacity. Events supports this in three ways:

### Soft-deleting purchased tickets

Soft-deleting a purchased ticket in the control panel moves it to the trash and immediately stops it from counting toward ticket type, session, and event capacity.

This is the recommended way to release a single ticket or a partial cancellation.

### Order status changes

When a Commerce order moves to one of the configured **Release Capacity Order Statuses** (Settings → Events → Tickets), Events automatically soft-deletes all purchased tickets for that order.

The default handles are `cancelled`, `canceled`, and `refunded`. Update these to match your store’s order statuses.

### Permanently deleting trashed tickets

Soft-deleted purchased tickets remain in the database until they are permanently deleted. Permanent deletion is optional for capacity purposes, but useful for cleanup and data retention.

You can permanently delete trashed purchased tickets from the control panel trash, with the console command:

```bash
./craft events/purchased-tickets/purge-trashed --dry-run=0
```

Or automatically after a retention period via **Trashed Purchased Ticket Retention** in plugin settings. When retention is enabled, Craft’s garbage collection will hard-delete trashed purchased tickets older than the configured number of days.
