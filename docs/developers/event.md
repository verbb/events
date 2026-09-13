# Event
Whenever you’re dealing with an event in your template, you’re actually working with a `Event` object.

<span id="attributes"></span>

## Properties

::: reference
### `id`

**Type:** `int|null`

The ID of the event.
:::

::: reference
### `title`

**Type:** `string|null`

The event’s title.
:::

::: reference
### `url`

**Type:** `string|null`

The URL to this single event.
:::

::: reference
### `type`

**Type:** `verbb\events\models\EventType`

The event’s type (as defined by its event type).
:::

::: reference
### `typeId`

**Type:** `int|null`

The ID of the event’s type.
:::

::: reference
### `status`

**Type:** `string|null`

The current status of the event: `live`, `pending`, or `expired`, determined based on `startDate`, `endDate`, `postDate`, and `expiryDate`.
:::

::: reference
### `enabled`

**Type:** `bool`

Whether the event is enabled (`true` or `false`).
:::

::: reference
### `capacity`

**Type:** `int|null`

The total capacity of tickets available for this event.
:::

::: reference
### `startDate`

**Type:** `DateTime|null`

The start date of the event (based on the first session).
:::

::: reference
### `endDate`

**Type:** `DateTime|null`

The end date of the event (based on the last session).
:::

::: reference
### `postDate`

**Type:** `DateTime|null`

The date when this event becomes available (i.e., when it’s posted).
:::

::: reference
### `expiryDate`

**Type:** `DateTime|null`

The date after which this event will no longer be available.
:::


## Methods

::: reference
### `getCpEditUrl()`

**Returns:** `string|null`

Returns the URL to edit this event in the control panel.
:::

::: reference
### `getSessions()`

**Returns:** `verbb\events\elements\SessionCollection`

Returns a collection of [Session](docs:developers/session) objects associated with this event.
:::

::: reference
### `getTicketTypes()`

**Returns:** `verbb\events\elements\TicketTypeCollection`

Returns a collection of [TicketType](docs:developers/ticket-type) objects associated with this event.
:::

::: reference
### `getTickets()`

**Returns:** `verbb\events\elements\TicketCollection`

Returns a collection of [Ticket](docs:developers/ticket) objects generated for this event.
:::

::: reference
### `getAvailableTickets()`

**Returns:** `verbb\events\elements\TicketCollection`

Returns a collection of available [Ticket](docs:developers/ticket) objects for sale. This respects the 'Available From/To' dates, along with ticket capacity and sales status.
:::

::: reference
### `getIcsUrl()`

**Returns:** `string`

Returns a URL to download the ICS (iCalendar) file for this single event. For multi-session events this spans all sessions; use [Session::getIcsUrl()](docs:developers/session) for a specific session.
:::

::: reference
### `getIsAvailable()`

**Returns:** `bool`

Indicates if the event is available for purchase. This will be `false` if there are no tickets available for sale, meaning the event is completely sold out.
:::
