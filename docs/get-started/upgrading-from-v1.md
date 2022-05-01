# Upgrading from v1
While the [changelog](https://github.com/verbb/events/blob/craft-4/CHANGELOG.md) is the most comprehensive list of changes, this guide provides high-level overview and organizes changes by category.

## Renamed Classes
The following classes have been renamed.

Old | What to do instead
--- | ---
| `verbb\events\records\EventRecord` | `verbb\events\records\Event`
| `verbb\events\records\EventTypeRecord` | `verbb\events\records\EventType`
| `verbb\events\records\EventTypeSiteRecord` | `verbb\events\records\EventTypeSite`
| `verbb\events\records\PurchasedTicketRecord` | `verbb\events\records\PurchasedTicket`
| `verbb\events\records\TicketRecord` | `verbb\events\records\Ticket`
| `verbb\events\records\TicketTypeRecord` | `verbb\events\records\TicketType`