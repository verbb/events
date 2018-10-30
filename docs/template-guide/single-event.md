# Single Event

Once you've got a list of events, you'll want to allow your customers to drill-into a single event page for more detail. This takes many cues from the single Product page for Commerce.

You'll have access to a `event` variable, which represents the single event you're looking at. You can also interchangeably use `product` if you wish.

```twig
{% extends 'shop/_layouts/main' %}
{% block main %}

<div class="row product-details">
    <div class="six columns">
        {% include "shop/_images/product" with { class: 'u-max-full-width' } %}
    </div>
    <div class="six columns">

        <h1>{{ event.title }}</h1>

        <form method="POST">
            <input type="hidden" name="action" value="events/cart/add">
            <input type="hidden" name="redirect" value="shop/cart">
            {{ getCsrfInput() }}

            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                {% for ticket in craft.events.tickets.eventId(event.id) %}
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

        <p><a href="{{ url('shop/events') }}">&larr; Back to all events.</a></p>
    </div>
</div>

{% endblock %}
```
