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
`ticketSku` | The generated ticket SKU.
`checkedIn` | `True` or `false` depending on whether the ticket has been checked in for the event.
`checkedInDate` | The date this ticket was checked in.