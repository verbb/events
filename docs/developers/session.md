# Session
Whenever you’re dealing with a session in your template, you’re working with a `Session` object.

<span id="attributes"></span>

## Properties

::: reference
### `id`

**Type:** `int|null`

The ID of the session.
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

The title of the session (auto-generated based on the Event Type’s settings).
:::

::: reference
### `startDate`

**Type:** `DateTime|null`

The date and time the session starts.
:::

::: reference
### `endDate`

**Type:** `DateTime|null`

The date and time the session ends.
:::

::: reference
### `allDay`

**Type:** `bool`

Whether this session spans the entire day (true or false).
:::

::: reference
### `status`

**Type:** `string|null`

The status of the session (e.g., `live`, `pending`, or `expired`).
:::


## Methods

::: reference
### `getCpEditUrl()`

**Returns:** `string|null`

Returns the URL to edit this session in the control panel.
:::

::: reference
### `getTickets()`

**Returns:** `verbb\events\elements\TicketCollection`

Returns a collection of [Ticket](docs:developers/ticket) objects for this session.
:::

::: reference
### `getIcsUrl()`

**Returns:** `string`

Returns a URL to download the ICS (iCalendar) file for this single session.
:::
