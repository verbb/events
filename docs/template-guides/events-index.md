# Events Index

Create a file named `index.html` file in your `templates/shop/events` folder. This folder may vary depending on your chosen site structure. Enter the content as below:

:::tip
We're just using the default Commerce templates here, so change this to your needs.
:::

```twig
{% extends 'shop/_layouts/main' %}

{% block main %}
    {% for event in craft.events.events.all() %}
        <div class="md:flex product bg-white mb-4 p-8 rounded items-center text-center md:text-left">
            <div class="md:w-2/6 md:p-4">
                <h3>{% if event.url %}{{ event.link }}{% else %}{{ event.title }}{% endif %}</h3>

                {% if event.isAvailable %}
                    <form method="POST" class="add-to-cart-form">
                        <input type="hidden" name="action" value="commerce/cart/update-cart">
                        {{ redirectInput('shop/cart') }}
                        {{ csrfInput() }}

                        <input type="number" name="qty" value="1">

                        <select name="purchasableId" class="purchasableId">
                            {%- for ticket in event.availableTickets() -%}
                                <option value="{{ ticket.purchasableId }}" {% if not ticket.isAvailable %}disabled{% endif %}>
                                    {{ ticket.name }} - {{ ticket.price | commerceCurrency(cart.currency) }}
                                </option>
                            {%- endfor -%}
                        </select>

                        <button type="submit">Add to cart</button>
                    </form>
                {% else %}
                    <strong>Sold out</strong>
                {% endif %}
            </div>
        </div>
    {% endfor %}
{% endblock %}

```

### Past Events

The `craft.events.events()` call will by default only return events that are currently on, or upcoming events. This is because the default query returned will be limiting event elements that have an end date greater than, or equal to the current time.

If you'd like to show past events, you can use the following:

```twig
{% for event in craft.events.events({ endDate: null }).all() %}
    ...
{% endfor %}
```

See [Event Queries](docs:getting-elements/event-queries) for more information.
