# Ticket Type
Whenever you’re dealing with a ticket type in your template, you’re working with a `TicketType` object.

<span id="attributes"></span>

## Properties

::: reference
### `id`

**Type:** `int|null`

The ID of the ticket type.
:::

::: reference
### `event`

**Type:** `verbb\events\elements\Event|null`

The associated [Event](docs:developers/event).
:::

<span id="eventid"></span>

::: reference
### `title`

**Type:** `string|null`

The name of the ticket type (e.g., VIP, General Admission).
:::

::: reference
### `price`

**Type:** `float|null`

The price for tickets of this type.
:::

::: reference
### `capacity`

**Type:** `int|null`

The maximum number of tickets of this type that can be sold.
:::

::: reference
### `availableFrom`

**Type:** `DateTime|null`

The date from which this ticket type is available for sale.
:::

::: reference
### `availableTo`

**Type:** `DateTime|null`

The date until which this ticket type is available for sale.
:::

<span id="description"></span>


## Methods

::: reference
### `getCpEditUrl()`

**Returns:** `string|null`

Returns the URL to edit this ticket type in the control panel.
:::

::: reference
### `getTickets()`

**Returns:** `verbb\events\elements\TicketCollection`

Returns a collection of [Ticket](docs:developers/ticket) objects for this ticket type.
:::

::: reference
### `getIsAvailable()`

**Returns:** `bool`

Returns true if this ticket type is available for sale based on the `availableFrom` and `availableTo` dates.
:::

<span id="getcapacity"></span>

::: reference
### `getPrice()`

**Returns:** `float|null`

Returns the price for this ticket type.
:::
