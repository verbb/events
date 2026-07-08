# Purchased Ticket
Whenever you’re dealing with a purchased ticket in your template, you’re actually working with a `PurchasedTicket` object.

## Attributes

Attribute | Description
--- | ---
`id` | The ID of the purchased ticket in the system.
`event` | The [Event](docs:developers/event) the purchased ticket is generated for.
`eventId` | The ID of the event the purchased ticket is generated for.
`session` | The [Session](docs:developers/session) the purchased ticket is generated for.
`sessionId` | The ID of the session the purchased ticket is generated for.
`ticket` | The [Ticket](docs:developers/ticket) the purchased ticket is generated for.
`ticketId` | The ID of the ticket the purchased ticket is generated for.
`order` | The [Order](https://craftcommerce.com/docs/order-model) where the ticket was originally purchased from.
`orderId` | The ID of the order where the ticket was originally purchased from.
`lineItem` | The [Line Item](https://craftcommerce.com/docs/line-item-model) in the order where the ticket was originally purchased from.
`lineItemId` | The ID of the line item where the ticket was originally purchased from.
`eventType` | The event’s type the purchased ticket is generated for.
`ticketType` | The ticket’s type the purchased ticket is generated for.
`eventName` | The name of the event the purchased ticket is generated for.
`ticketName` | The name of the ticket the purchased ticket is generated for.
`qrCode` | A QR code with a URL to the controller, including the ticket SKU, to easily check in a ticket for the event.
`checkedIn` | `True` or `false` depending on whether the ticket has been checked in for the event.
`checkedInDate` | The date this ticket was checked in.
`reservationStatus` | The reservation status: `active` or `cancelled`.
`cancelledAt` | When the reservation was cancelled.
`cancelledReason` | Why the reservation was cancelled.
`isActive` | Whether the purchased ticket is an active reservation.
`isCancelled` | Whether the purchased ticket has been cancelled.

## Cancelling Tickets and Releasing Capacity

Purchased tickets represent seat reservations. To return seats to available capacity, cancel the reservation rather than deleting the record.

### Manual cancellation

Use **Cancel tickets** from **Events → Purchased Tickets** in the control panel. This marks the reservation as cancelled, releases capacity, blocks check-in, and keeps the record for reporting.

Use **Restore tickets** to make a cancelled reservation active again.

### Order cancellations and refunds

Commerce order refunds do not remove purchased tickets by themselves. Events listens for:

- **Order status changes** to configured release-capacity statuses
- **Commerce refunds**, when enabled in Settings → Events → Tickets

See [Capacity](docs:feature-tour/capacity) for more detail on how capacity is calculated.

### Deleting purchased tickets

Deletion is for mistaken records or GDPR cleanup only. Cancel tickets first, then delete permanently if needed.

```bash
./craft events/purchased-tickets/purge-trashed --dry-run=0
```
