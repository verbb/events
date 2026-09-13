# Finding Events and Tickets

Start with the type of record you need: an event describes the overall activity, a session supplies a date, and a ticket represents a session and ticket-type combination. Execute the corresponding query after applying its filters.

```twig
{% set events = craft.events.events().all() %}
{% for event in events %}
    <h2>{{ event.title }}</h2>
{% endfor %}
```

## Calls Used in This Task

### `craft.events.events.endDate('>= now')`
Returns an [Event Query](docs:getting-elements/event-queries) for you to modify and output events.

:::warning
By default, only current events will be returned when calling `craft.events.events()`. To change this, use the `craft.events.events.endDate(null)`. Events are also ordered from the oldest startDate to the newest, which you can also change with the `orderBy` parameter.
:::

### `craft.events.sessions()`
See [Session Queries](docs:getting-elements/session-queries)

### `craft.events.tickets()`
See [Ticket Queries](docs:getting-elements/ticket-queries)

### `craft.events.purchasedTickets()`
Returns all purchased tickets based on the provided criteria. See [Purchased Ticket](docs:getting-elements/purchased-ticket-queries)

