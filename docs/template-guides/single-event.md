# Single Event

Once you've got a list of events, you'll want to allow your customers to drill-into a single event page for more detail. This takes many cues from the single Product page for Commerce.

You'll have access to a `event` variable, which represents the single event you're looking at. You can also interchangeably use `product` if you wish.

```twig
{% extends 'shop/_layouts/main' %}

{% block main %}
    <div class="mt-8">
        <a href="{{ url('shop/products') }}">&larr; All products</a>
    </div>

    <div class="flex -mx-6 mt-8 product-details">
        <div class="w-1/2 mx-6 p-8">

        </div>
        <div class="w-1/2 mx-6 p-8">
            <h1>{{ event.title }}</h1>

            {% if event.isAvailable %}
                <form method="POST">
                    <input type="hidden" name="action" value="commerce/cart/update-cart">
                    {{ redirectInput('shop/cart') }}
                    {{ csrfInput() }}

                    <input type="number" name="qty" value="1">

                    <div class="field">
                        <select name="purchasableId">
                            {% for ticket in event.tickets %}
                                <option value="{{ ticket.purchasableId }}" {% if not ticket.isAvailable %}disabled{% endif %}>
                                    {{ ticket.name }} - {{ ticket.price | commerceCurrency(cart.currency) }}
                                </option>
                            {% endfor %}
                        </select>
                    </div>
                    <div class="buttons">
                        <input type="submit" value="Add to cart" class="button"/>
                    </div>
                </form>
            {% else %}
                <strong>Sold out</strong>
            {% endif %}
        </div>
    </div>
{% endblock %}
```

The above shows all tickets for an event, whether they're available or not. We use `ticket.isAvailable` to see if we can purchase this ticket, as it could be sold out.

If you wanted to only show the available tickets, and not show sold out ones, you could loop through `event.availableTickets()` instead.

```twig
<select>
    {%- for ticket in event.availableTickets() -%}
        <option value="{{ ticket.purchasableId }}">
            {{ ticket.name }} - {{ ticket.price | commerceCurrency(cart.currency) }}
        </option>
    {% endfor %}
</select>
```

In addition, the check for `event.isAvailable` checks whether all tickets are sold out (or unavailable), and if so, will show a 'Sold Out' notice, that no tickets for this event are available.