# Available Variables

The following are common methods you will want to call in your front end templates:

### `craft.events.events({ endDate: '>= now' })`

Returns an [Event Query](docs:getting-elements/event-queries) for you to modify and output events.

:::warning
By default, only current events will be returned when calling `craft.events.events()`. To change this, use the `craft.events.events({ endDate: null })`. Events are also ordered from the oldest startDate to the newest, which you can also change with the `orderBy` parameter.
:::

### `craft.events.tickets()`

See [Ticket Queries](docs:getting-elements/ticket-queries)

### `craft.events.purchasedTickets()`

Returns all purchased tickets based on the provided criteria. See [Purchased Ticket](docs:getting-elements/purchased-ticket-queries)

### `craft.events.isTicket(lineItem)`

Returns whether a provided Line Item object is a ticket or not.

### `craft.events.hasTicket(order)`

Returns if there is at least one ticket in the provided order.

### `craft.events.getPdfUrl(lineItem)`

Returns a URL to the PDF for this ticket for the provided Line Item object. This will only show tickets for this line item.

### `craft.events.getOrderPdfUrl(order)`

Returns a URL to the PDF for all tickets for the provided Order object. This will show tickets for the entire order.

### `craft.events.getEventTypes()`

Returns all Event Types available.

### `craft.events.getEditableEventTypes()`

Returns all Event Types the current user has permission to.

### `craft.events.getTicketTypes()`

Returns all Ticket Types available.

### `craft.events.getEditableTicketTypes()`

Returns all Ticket Types the current user has permission to.
