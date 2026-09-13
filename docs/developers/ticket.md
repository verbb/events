# Ticket
Whenever you’re dealing with a ticket in your template, you’re actually working with a `Ticket` object.

<span id="attributes"></span>

## Properties

::: reference
### `id`

**Type:** `int|null`

The ID of the ticket.
:::

::: reference
### `title`

**Type:** `string|null`

The ticket title.
:::

::: reference
### `event`

**Type:** `Event|null`

The ticket’s associated [Event](docs:developers/event).
:::

::: reference
### `eventId`

**Type:** `int|null`

The ID of the event this ticket belongs to.
:::

::: reference
### `type`

**Type:** `TicketType|null`

The ticket’s associated ticket type.
:::

::: reference
### `typeId`

**Type:** `int|null`

The ID of the ticket’s type.
:::

::: reference
### `sku`

The SKU (Stock Keeping Unit) of the ticket.
:::

<span id="quantity"></span>

::: reference
### `price`

**Type:** `float`

The listing price of the ticket.
:::

<span id="availablefrom"></span>

<span id="availableto"></span>


## Methods

::: reference
### `getCpEditUrl()`

Returns the URL to edit this ticket in the control panel.
:::

<span id="getpurchasedticketslineitem"></span>

::: reference
### `getIsAvailable()`

**Returns:** `bool`

Whether this ticket is available for purchase. This will be `true` unless the 'Available From/To' dates do not match the current time, or if the ticket has been fully purchased.
:::

## Read Session and Type Details

A Ticket combines a session and a ticket type. Read the date from its session and the sale window from its type. For example, in a template where `ticket` is a Ticket returned by a [ticket query](docs:getting-elements/ticket-queries):

```twig
{% if ticket.session %}
    <p>{{ ticket.session.startDate | date('medium') }}</p>
{% endif %}

{% if ticket.type and ticket.type.availableFrom %}
    <p>Available from {{ ticket.type.availableFrom | date('medium') }}</p>
{% endif %}
```

Use the cart or order line item for the quantity being purchased. A generated Ticket is the purchasable definition, not a customer's line-item quantity.
