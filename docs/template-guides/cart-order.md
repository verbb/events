# Cart & Order

When you're on your cart page, or on an order summary screen, you'll likely want to tailor the output of purchasing an event differently to other purchasables in your order.

Using the following, you can check to see if a line item is a ticket, and show various things based on that.

```twig
{% if craft.events.isTicket(item) %}
    {% set ticket = item.purchasable %}

    {% for purchasedTicket in ticket.getPurchasedTickets(item) %}
        Ticket-SKU: {{ purchasedTicket }}<br />
    {% endfor %}
{% endif %}
```

:::tip
Because multiple tickets can be purchased for one line item, its important to loop through potentially multiple unique ticket SKU's as above.
:::

If you're looking for something a little prettier than simply showing the ticket SKU in your order confirmation page or email, you may want to look at generating a [PDF Template](docs:template-guides/pdf-template).