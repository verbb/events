# Events Index
Create a file named `index.html` file in your `templates/shop/events` folder. This folder may vary depending on your chosen site structure. Enter the content as below:

```twig
{% for event in craft.events.events.all() %}
    <h3>{{ event.title }}</h3

    {% if event.isAvailable %}
        <form method="POST" class="add-to-cart-form">
            {{ actionInput('commerce/cart/update-cart') }}
            {{ csrfInput() }}

            <input type="number" name="qty" value="1">

            <select name="purchasableId">
                {%- for ticket in event.getTickets() -%}
                    <option value="{{ ticket.id }}" {% if not ticket.isAvailable %}disabled{% endif %}>
                        {{ ticket.title }} - {{ ticket.price | commerceCurrency(cart.currency) }}
                    </option>
                {%- endfor -%}
            </select>

            <button type="submit">Add to cart</button>
        </form>
    {% else %}
        <strong>Sold out</strong>
    {% endif %}
{% endfor %}
```

If you wanted to only show the available tickets, and not show sold out ones, you could loop through `event.availableTickets()` instead.

```twig
<select>
    {%- for ticket in event.availableTickets() -%}
        <option value="{{ ticket.id }}">
            {{ ticket.title }} - {{ ticket.price | commerceCurrency(cart.currency) }}
        </option>
    {% endfor %}
</select>
```

### Past Events
The `craft.events.events()` call will by default only return events that are currently on, or upcoming events. This is because the default query returned will be limiting event elements that have an end date greater than, or equal to the current time.

If you'd like to show past events, you can use the following:

```twig
{% for event in craft.events.events.endDate(null).all() %}
    ...
{% endfor %}
```

See [Event Queries](docs:getting-elements/event-queries) for more information.
