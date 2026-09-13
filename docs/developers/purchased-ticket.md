# Purchased Ticket
Whenever you’re dealing with a purchased ticket in your template, you’re actually working with a `PurchasedTicket` object.

<span id="attributes"></span>

## Properties

::: reference
### `id`

**Type:** `int|null`

The ID of the purchased ticket in the system.
:::

::: reference
### `event`

**Type:** `verbb\events\elements\Event|null`

The [Event](docs:developers/event) the purchased ticket is generated for.
:::

::: reference
### `eventId`

**Type:** `int|null`

The ID of the event the purchased ticket is generated for.
:::

::: reference
### `session`

**Type:** `verbb\events\elements\Session|null`

The [Session](docs:developers/session) the purchased ticket is generated for.
:::

::: reference
### `sessionId`

**Type:** `int|null`

The ID of the session the purchased ticket is generated for.
:::

::: reference
### `ticket`

**Type:** `verbb\events\elements\Ticket|null`

The [Ticket](docs:developers/ticket) the purchased ticket is generated for.
:::

::: reference
### `ticketId`

**Type:** `int|null`

The ID of the ticket the purchased ticket is generated for.
:::

::: reference
### `order`

**Type:** `craft\commerce\elements\Order|null`

The [Order](https://craftcommerce.com/docs/order-model) where the ticket was originally purchased from.
:::

::: reference
### `orderId`

**Type:** `int|null`

The ID of the order where the ticket was originally purchased from.
:::

::: reference
### `lineItem`

**Type:** `craft\commerce\models\LineItem|null`

The [Line Item](https://craftcommerce.com/docs/line-item-model) in the order where the ticket was originally purchased from.
:::

::: reference
### `lineItemId`

**Type:** `int|null`

The ID of the line item where the ticket was originally purchased from.
:::

<span id="eventtype"></span>

::: reference
### `ticketType`

**Type:** `verbb\events\elements\TicketType|null`

The ticket’s type the purchased ticket is generated for.
:::

<span id="eventname"></span>

<span id="ticketname"></span>

::: reference
### `qrCode`

**Type:** `string`

A QR code with a URL to the controller, including the ticket SKU, to easily check in a ticket for the event.
:::

::: reference
### `checkedIn`

**Type:** `bool|null`

`True` or `false` depending on whether the ticket has been checked in for the event.
:::

::: reference
### `checkedInDate`

**Type:** `DateTime|null`

The date this ticket was checked in.
:::

::: reference
### `reservationStatus`

**Type:** `string`

The reservation status: `active` or `cancelled`.
:::

::: reference
### `cancelledAt`

**Type:** `DateTime|null`

When the reservation was cancelled.
:::

::: reference
### `cancelledReason`

**Type:** `string|null`

Why the reservation was cancelled.
:::

::: reference
### `isActive`

**Type:** `bool`

Whether the purchased ticket is an active reservation.
:::

::: reference
### `isCancelled`

**Type:** `bool`

Whether the purchased ticket has been cancelled.
:::


## Cancelling Tickets and Releasing Capacity

Purchased tickets represent seat reservations. To return seats to available capacity, cancel the reservation rather than deleting the record.

### Manual Cancellation

Use **Cancel tickets** from **Events → Purchased Tickets** in the control panel. This marks the reservation as cancelled, releases capacity, blocks check-in, and keeps the record for reporting.

Use **Restore tickets** to make a cancelled reservation active again.

### Order Cancellations and Refunds

Commerce order refunds do not remove purchased tickets by themselves. Events listens for:

- **Order status changes** to configured release-capacity statuses
- **Commerce refunds**, when enabled in Settings → Events → Tickets

See [Capacity](docs:feature-tour/capacity) for more detail on how capacity is calculated.

### Deleting Purchased Tickets

Deletion is for mistaken records or GDPR cleanup only. Cancel tickets first, then delete permanently if needed.

```bash
./craft events/purchased-tickets/purge-trashed --dry-run=0
```
