# Displaying Ticket Downloads

Use an authorised Commerce order to show ticket downloads. Check whether a line item is a ticket before adding its PDF link. An order-level link includes the tickets for the entire order. Do not expose another customer’s order through a public order-ID lookup.

The download links contain the order's number, which is what gives access to the PDF, so only show them to the customer who placed the order. A completed order's confirmation page, the customer's account area, or an order email are all suitable places. The PDF itself is rendered from your [PDF template](docs:template-guides/pdf-template).

This example belongs on an order confirmation page, where Commerce provides the completed order as `order`.

```twig
{% if craft.events.hasTicket(order) %}
    <a href="{{ craft.events.getOrderPdfUrl(order) }}">Download all tickets</a>

    <ul>
        {% for lineItem in order.lineItems %}
            {% if craft.events.isTicket(lineItem) %}
                <li>
                    {{ lineItem.description }}
                    <a href="{{ craft.events.getPdfUrl(lineItem) }}">Download</a>
                </li>
            {% endif %}
        {% endfor %}
    </ul>
{% endif %}
```

Customers who bought tickets see one link for every ticket in the order, followed by a link for each ticket line item. Orders without tickets show nothing.

## Checking for Tickets

`craft.events.isTicket(lineItem)` returns `true` when the given Commerce line item is for an Events ticket, and `false` for any other purchasable or a line item whose purchasable no longer exists. `lineItem` is required. Use it inside a loop over `order.lineItems` or `cart.lineItems` to treat tickets differently from other products.

`craft.events.hasTicket(order)` returns `true` when at least one line item in the given order is a ticket. `order` is required. Use it to decide whether to show a tickets section at all.

## Linking to Ticket PDFs

`craft.events.getPdfUrl(lineItem)` returns the URL of a PDF containing only the tickets for the given line item, or `null` if the line item isn't a ticket. `lineItem` is required and must belong to a completed order, because the download looks up the order by its number.

`craft.events.getOrderPdfUrl(order)` returns the URL of a PDF containing every ticket in the given order. `order` is required and must be completed. A URL is returned for any order, so check `craft.events.hasTicket(order)` first, as the download returns a "not found" error for an order without tickets.
