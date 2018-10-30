# Fetching Events

Because events are a custom element type, they won't automatically appear on your shop without a bit of templating. Fortunately, implementing these templates are straightforward, and you'll find it quite similar to Commerce.

You can display a list of all events via the following template snippet:

```twig
{% for event in craft.events.events.find() %}
```

Like [Variants](https://craftcommerce.com/docs/variant-model), events are elements, meaning you have access to familiar querying via the [Craft Element Criteria Model](https://craftcms.com/docs/templating/elementcriteriamodel). For instance you can limit events via `limit()`.

```twig
{% for event in craft.events.events.limit(5).find() %}
```

Or select events of a specific `type`.

```twig
{% for event in craft.events.events.type('festival').find() %}
```

### Template

Create a file named `index.html` file in your `templates/shop/products/events` folder. This folder may vary depending on your chosen site structure. Enter the content as below:

:::tip
We're just using the default Commerce templates here, so change this to your needs.
:::

```twig
{% extends 'shop/_layouts/main' %}
{% block main %}

{% for event in craft.events.events.limit(5).find() %}
    <div class="row product">
        <div class="two columns">
            {% include "shop/_images/product" with { class: 'u-max-full-width' } %}
        </div>
        <div class="ten columns">
            <h5>{% if event.url %}{{ event.link }}{% else %}{{ event.title }}{% endif %}</h5>

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
        </div>
    </div>
{% endfor %}

{% endblock %}
```
