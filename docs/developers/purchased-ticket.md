# Purchased Ticket

Whenever you're dealing with a purchased ticket in your template, you're actually working with a `PurchasedTicket` object.

## Attributes

Attribute | Description
--- | ---
`id` | The id of the purchased ticket in the system.
`event` | The [Event](docs:developers/event) the purchased ticket is generated for.
`eventId` | The event's id the purchased ticket is generated for.
`ticket` | The [Ticket](docs:developers/ticket) the purchased ticket is generated for.
`ticketId` | The ticket's id the purchased ticket is generated for.
`order` | The [Order](https://craftcommerce.com/docs/order-model) where the ticket was originally purchased from.
`orderId` | The order's id where the ticket was originally purchased from.
`lineItem` | The [Line Item](https://craftcommerce.com/docs/line-item-model) in the order where the ticket was originally purchased from.
`lineItemId` | The lineItem's id where the ticket was originally purchased from.
`eventType` | The event's type the purchased ticket is generated for.
`ticketType` | The ticket's type the purchased ticket is generated for.
`eventName` | The event's name the purchased ticket is generated for.
`ticketName` | The ticket's name the purchased ticket is generated for.
`qrCode` | A QR code with the url to the controller including the ticket sku to easily check in a ticket to an event.
`ticketSku` | The generated ticket sku.
`checkedIn` | True or false if the the ticket was already checked in for that event.
`checkedInDate` | The date this ticket was checked in.