# Ticket
Whenever you’re dealing with a ticket in your template, you’re actually working with a `Ticket` object.

## Attributes

Attribute | Description
--- | ---
`id` | The ID of the ticket.
`title` | The ticket title.
`event` | The ticket’s associated [Event](docs:developers/event).
`eventId` | The ID of the event this ticket belongs to.
`type` | The ticket’s associated ticket type.
`typeId` | The ID of the ticket’s type.
`sku` | The SKU (Stock Keeping Unit) of the ticket.
`quantity` | The quantity of this ticket.
`price` | The listing price of the ticket.
`availableFrom` | The date from which this ticket is available for sale.
`availableTo` | The date until which this ticket is available for sale.
`isAvailable` | Whether this ticket is available for purchase. This will be `true` unless the 'Available From/To' dates do not match the current time, or if the ticket has been fully purchased.

## Methods

Method | Description
--- | ---
`getCpEditUrl()` | Returns the URL to edit this ticket in the control panel.
`getPurchasedTickets(lineItem)` | Returns all [Purchased Tickets](docs:developers/purchased-ticket) associated with this ticket for a given line item.