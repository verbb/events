# Displaying Tickets

Tickets are handled similar to events, and can query via the [Craft Element Criteria Model](https://craftcms.com/docs/templating/elementcriteriamodel). To get all tickets for a specified event you need to provide the `eventId`.

```twig
{% for ticket in craft.events.tickets.eventId(event.id) %}
```

### Available Tickets

Because tickets can have a `Available From` and `Available To` parameter you need to be aware of this. Events provides a simple helper function to get all available tickets. The `eventId` is also required again.

```twig
{% for ticket in craft.events.availableTickets(event.id) %}
```

### Purchased Tickets

Purchased tickets are stored in a extra table. You can get these via `craft.events.purchasedTickets()`. You can give this function up to 2 parameters:

- `attributes`: list of attribute values (indexed by attribute names) that the active records should match.
- `options`: query condition or criteria.

#### Event Capacity

Events can have a maximum capacity, which you can check against:

```twig
{% set purchasedEventTickets = craft.events.purchasedTickets({ eventId: event.id }) | length %}
{% if purchasedEventTickets < event.capacity %}
    // Your ticket list here...
{% endif %}
```

#### Ticket Quantity

Tickets can have a maximum quantity, which you can also check against:

```twig
{% set purchasedTickets = craft.events.purchasedTickets({ ticketId: ticket.id }) | length %}
{% if purchasedTickets < ticket.quantity %}
    //...
{% endif %}
```

#### Simple Ticket Selection

A simple ticket selection list can look like following template:

```twig
{# check for event limit #}
{% set purchasedEventTickets = craft.events.purchasedTickets({ eventId: event.id }) | length %}
{% if purchasedEventTickets < event.capacity %}
    <form method="POST">
        <input type="hidden" name="action" value="commerce/cart/updateCart">
        <input type="hidden" name="redirect" value="shop/cart">
        <input type="hidden" name="qty" value="1">
        {{ getCsrfInput() }}

        <select name="purchasableId" class="purchasableId">
            {%- for ticket in craft.events.availableTickets(event.id) -%}

                {# check for ticket limits #}
                {% set purchasedTickets = craft.events.purchasedTickets({ ticketId: ticket.id }) | length %}
                {% if purchasedTickets < ticket.quantity %}
                    <option value="{{ ticket.purchasableId }}">
                        {{ ticket }} - {{ ticket.price|commerceCurrency(cart.currency) }}
                    </option>
                {%- endif -%}
            {%- endfor -%}
        </select>

        <button type="submit">{{ "Add to cart"|t }}</button>
    </form>
{% else %}
    <strong>Sold out</strong>
{% endif %}
```

#### Multiple Ticket Selection

Events can be provided as a multi ticket selection list. To use it you need to call the custom front-end controller `events/cart/add`.

A complete integration can look like following template:

```twig
<form method="POST">
    {# custom frontend controller #}
    <input type="hidden" name="action" value="events/cart/add">
    <input type="hidden" name="redirect" value="shop/cart">
    {{ getCsrfInput() }}

    <table width="100%" border="0" cellpadding="0" cellspacing="0">
        {% for ticket in craft.events.availableTickets(event.id) %}

            <tr>
                <td>{{ ticket }}</td>
                <td>{{ ticket.price | commerceCurrency(cart.currency) }}</td>

                <td align="right" nowrap="nowrap">
                    <input type="hidden" name="event" value="{{ event.id }}">

                    {# check for ticket limits #}
                    {% set purchasedTickets = craft.events.purchasedTickets({ ticketId: ticket.id }) | length %}
                    {% set availableTickets = ticket.quantity - purchasedTickets %}

                    {% if availableTickets > 0 %}
                        <div class="field dropdown">
                            <div class="input">
                                <select name="item[{{ ticket.id }}][qty]" class="ticket_table_select">
                                    {% set maxDropdown = (availableTickets > 10) ? 10 : availableTickets %}

                                    {% for i in 0..maxDropdown %}
                                        <option value="{{ i }}">{{ i }}</option>
                                    {% endfor %}
                                </select>
                            </div>
                        </div>
                    {% else %}
                        <strong>Sold out</strong>
                    {% endif %}
                </td>
            </tr>

        {% endfor %}
    </table>

    <input type="submit" value="{{ "Add to cart"|t }}" class="button"/>
</form>
```

### Displaying Purchased Ticket SKU's

Once a user has purchased a ticket, its important to actually provide the ticket SKU for them to use at the event. For example, on your order summary template (`shop/customer/order.html` for example), you could have the following code looping through line items for the order.

```twig
{% if craft.events.isTicket(item) %}
    {% for purchasedTicket in item.purchasable.getPurchasedTicketsForLineItem(item) %}
        Ticket-SKU: {{ purchasedTicket }}<br />
    {% endfor %}
{% endif %}
```

:::tip
Because multiple tickets can be purchased for one line item, its important to loop through potentially multiple unique ticket SKU's as above.
:::

If you're looking for something a little prettier than simply showing the ticket SKU in your order confirmation page or email, you may want to look at generating a [PDF Template](/craft-plugins/events/docs/template-guide/pdf-template).