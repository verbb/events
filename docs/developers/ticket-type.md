# Ticket Type
Whenever you’re dealing with a ticket type in your template, you’re working with a `TicketType` object.

## Attributes

Attribute | Description
--- | ---
`id` | The ID of the ticket type.
`event` | The associated [Event](docs:developers/event).
`eventId` | The ID of the event this ticket type belongs to.
`title` | The name of the ticket type (e.g., VIP, General Admission).
`price` | The price for tickets of this type.
`capacity` | The maximum number of tickets of this type that can be sold.
`availableFrom` | The date from which this ticket type is available for sale.
`availableTo` | The date until which this ticket type is available for sale.
`description` | A description of this ticket type.

## Methods

Method | Description
--- | ---
`getCpEditUrl()` | Returns the URL to edit this ticket type in the control panel.
`getTickets()` | Returns a collection of [Ticket](docs:developers/ticket) objects for this ticket type.
`isAvailable()` | Returns true if this ticket type is available for sale based on the `availableFrom` and `availableTo` dates.
`getCapacity()` | Returns the total capacity for this ticket type, or `null` if no capacity is set.
`getPrice()` | Returns the price for this ticket type.