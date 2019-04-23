# Ticket

Whenever you're dealing with a ticket in your template, you're actually working with a `Ticket` object.

## Attributes

Attribute | Description
--- | ---
`id` | ID of the ticket.
`title` | The ticket name/title.
`name` | The ticket name/title.
`purchasableId` | Returns this ticket id - as ticket's are purchasables.
`event` | The ticket's [Event](docs:developers/event).
`eventId` | The ticket's event ID
`type` | The ticket's ticket type.
`typeId` | The ticket's ticket type ID
`sku` | The sku of the ticket.
`quantity` | The quantity of the ticket.
`price` | The listing price of the ticket.
`availableFrom` | The date this ticket is available for sale.
`availableTo` | The date this ticket will no longer be available for sale.
`taxCategory` | The tax category this ticket uses when their tax calculations are made.
`shippingCategory` | The shipping category this ticket uses when their shipping calculations are made.
`isAvailable` | Whether this ticket is available for purchase. This will be true, unless the 'Available To/From' date ranges (when set) do not fit the current time. Will also check for purchased tickets for this ticket.

## Methods

Method | Description
--- | ---
`getCpEditUrl()` | The url to edit this ticket in the control panel.
`getPurchasedTickets(lineItem)` | Get all [Purchased Ticket's](docs:developers/purchased-ticket) for this ticket.
