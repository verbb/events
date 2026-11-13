# Finding Events and Tickets

Start with the type of record you need. An event describes the overall activity, a session supplies a date, and a ticket represents a session and ticket-type combination. Execute the corresponding query after applying its filters.

```twig
{% set events = craft.events.events()
    .orderBy('startDate asc')
    .limit(10)
    .all() %}

{% for event in events %}
    <h2>{{ event.title }}</h2>
    <p>{{ event.startDate|date('j F Y') }}</p>
{% endfor %}
```

This lists the next 10 current and upcoming events, soonest first. Each call below creates a query you can refine with parameters and then run with `.all()`, `.one()` or `.count()`. You can also pass the parameters as a hash, so `craft.events.events({ type: 'conference' })` is the same as `craft.events.events().type('conference')`.

## Finding Events

`craft.events.events(criteria)` returns an [event query](docs:getting-elements/event-queries) for listing events, such as on an events index or a calendar. `criteria` is an optional hash of query parameters.

:::warning
By default, `craft.events.events()` only returns current and upcoming events, because it sets `endDate` to the current date and time. Call `.endDate(null)` to include past events. Events are ordered by their post date, newest first, so use `.orderBy('startDate asc')` to list them by when they start.
:::

```twig
{# Past events, most recent first #}
{% set currentTime = now|atom %}

{% set pastEvents = craft.events.events()
    .endDate("< #{currentTime}")
    .orderBy('endDate desc')
    .all() %}
```

## Finding Sessions

`craft.events.sessions(criteria)` returns a [session query](docs:getting-elements/session-queries). Use it when you're listing dates, such as an event's upcoming sessions or every session on a given day. `criteria` is an optional hash of query parameters. Unlike `craft.events.events()`, it doesn't filter out past sessions unless you set `startDate` or `endDate`.

```twig
{% set today = date('today')|atom %}

{% set sessions = craft.events.sessions()
    .event(event)
    .startDate(">= #{today}")
    .orderBy('startDate asc')
    .all() %}
```

## Finding Tickets

`craft.events.tickets(criteria)` returns a [ticket query](docs:getting-elements/ticket-queries). Tickets are the purchasables you add to the cart, so use this query when you're building a ticket selection form. `criteria` is an optional hash of query parameters.

```twig
{% set tickets = craft.events.tickets()
    .event(event)
    .all() %}
```

To list the ticket types themselves, such as "Adult" and "Child", use a [ticket type query](docs:getting-elements/ticket-type-queries) with `craft.events.ticketTypes()`. `craft.events.getEditableTicketTypes()` returns an array of the ticket types the signed-in user can edit, across every event, or an empty array for a guest.

## Finding Purchased Tickets

`craft.events.purchasedTickets(criteria)` returns a [purchased ticket query](docs:getting-elements/purchased-ticket-queries) for the tickets customers have bought, newest first. Use it for attendee lists or a customer's own tickets. `criteria` is an optional hash of query parameters.

The query isn't limited to the current customer, so filter it yourself before showing purchased tickets on the front end.

```twig
{% if currentUser %}
    {% set purchasedTickets = craft.events.purchasedTickets()
        .customer(currentUser)
        .reservationStatus('active')
        .all() %}

    {% for purchasedTicket in purchasedTickets %}
        <p>{{ purchasedTicket.event.title }}, {{ purchasedTicket.ticketType.title }}</p>
    {% endfor %}
{% endif %}
```
