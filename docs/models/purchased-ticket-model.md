# Purchased Ticket Model

When a customer has purchased a ticket, a `Events_PurchasedTicketModel` will be automatically generated for each ticket in their cart. This model contains the unique sku for the customer to use to enter the associated event.

As such, you'll need this reference in particular when templating your [PDF template](/craft-plugins/events/docs/template-guide/pdf-template), or showing the resulting ticket in your order summary or email templates.

## Simple Output

Outputting a `Events_PurchasedTicketModel` object in your template without attaching a property or method will simply return the generated ticket sku.

`<h1>{{ ticket }}</h1>`

Purchased Ticket Models have the following attributes and methods:

## Attributes

### id

The id of the purchased ticket in the system.

### event

The [Event Model](/craft-plugins/events/docs/models/event-model) the purchased ticket is generated for.

### eventId

The event's id the purchased ticket is generated for.

### ticket

The [Ticket Model](/craft-plugins/events/docs/models/ticket-model) the purchased ticket is generated for.

### ticketId

The ticket's id the purchased ticket is generated for.

### order

The [Order Model](https://craftcommerce.com/docs/order-model) where the ticket was originally purchased from.

### orderId

The order's id where the ticket was originally purchased from.

### lineItem

The [Line Item Model](https://craftcommerce.com/docs/line-item-model) in the order where the ticket was originally purchased from.

### lineItemId

The lineItem's id where the ticket was originally purchased from.

### eventType

The event's type the purchased ticket is generated for.

### ticketType

The ticket's type the purchased ticket is generated for.

### eventName

The event's name the purchased ticket is generated for.

### ticketName

The ticket's name the purchased ticket is generated for.

### QR

A QR code with the url to the controller including the ticket sku to easily check in a ticket to an event.

### ticketSku

The generated ticket sku.

### checkedIn

True or false if the the ticket was already checked in for that event.

### checkedInDate

The date this ticket was checked in.