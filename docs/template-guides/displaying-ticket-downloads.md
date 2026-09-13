# Displaying Ticket Downloads

Use an authorised Commerce order to show ticket downloads. Check whether a line item is a ticket before adding its PDF link. An order-level link includes the tickets for the entire order. Do not expose another customer’s order through a public order-ID lookup.

## Calls Used in This Task

### `craft.events.isTicket(lineItem)`
Returns whether a provided Line Item object is a ticket or not.

### `craft.events.hasTicket(order)`
Returns if there is at least one ticket in the provided order.

### `craft.events.getPdfUrl(lineItem)`
Returns a URL to the PDF for this ticket for the provided Line Item object. This will only show tickets for this line item.

### `craft.events.getOrderPdfUrl(order)`
Returns a URL to the PDF for all tickets for the provided Order object. This will show tickets for the entire order.

