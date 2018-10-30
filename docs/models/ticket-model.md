# Ticket Model

Events contain multiple tickets. When you're looping through available tickets using `craft.events.tickets.eventId(event.id)`, you're actually working with a `Events_TicketModel`. This in turn extends Commerce's [Purchasable](https://craftcommerce.com/docs/purchasables) object.

## Simple Output

Outputting a `Events_TicketModel` object in your template without attaching a property or method will return the ticket’s name:

`<h1>{{ ticket }}</h1>`

Ticket Models have the following attributes and methods:

## Attributes

### title

The ticket name/title.

### id

The id of the ticket in the system.

### purchasableId

Returns this ticket id - as ticket's are purchasables.

### event

The ticket's [Event Model](/craft-plugins/events/docs/models/event-model).

### eventId

The ticket's event Id

### eventType

The ticket's event type.

### ticketType

The ticket's ticket type.

### ticketTypeId

The ticket's ticket type Id

### sku

The sku of the ticket.

### quantity

The quantity of the ticket.

### price

The listing price of the ticket.

### availableFrom

The date this ticket is available for sale.

### availableTo

The date this ticket will no longer be available for sale.

### taxCategory

The tax category this voucher uses when their tax calculations are made.

### shippingCategory

The shipping category this voucher uses when their shipping calculations are made.

### cpEditUrl

The url to edit this ticket.