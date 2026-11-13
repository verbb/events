# Selecting Event Types

Choose editable types when a form lets the current user create or edit content. The full type lists describe configuration and do not imply that the current user can edit every type.

Event types group your events, such as Conferences and Workshops, and each has a `name`, `handle` and `id`. A common use is a filter on an events index that lets visitors narrow the list to one type.

```twig
{% set selectedType = craft.app.request.getQueryParam('type') %}

<nav>
    <a href="{{ url('events') }}">All events</a>

    {% for eventType in craft.events.getEventTypes() %}
        <a href="{{ url('events', { type: eventType.handle }) }}">{{ eventType.name }}</a>
    {% endfor %}
</nav>

{% set eventsQuery = craft.events.events() %}

{% if selectedType %}
    {% do eventsQuery.type(selectedType) %}
{% endif %}

{% for event in eventsQuery.all() %}
    <h2>{{ event.title }}</h2>
{% endfor %}
```

The `type` parameter is described in [Event Queries](docs:getting-elements/event-queries).

## Listing Event Types

`craft.events.getEventTypes()` returns an array of every event type. It takes no arguments.

`craft.events.getEditableEventTypes()` returns an array of the event types the signed-in user has permission to edit events in. It takes no arguments. Use it for front-end forms that create or edit events, so a user only sees the types they can save to. For a guest, it returns an empty array.

```twig
{% set editableTypes = craft.events.getEditableEventTypes() %}

{% if editableTypes %}
    <select name="typeId">
        {% for eventType in editableTypes %}
            <option value="{{ eventType.id }}">{{ eventType.name }}</option>
        {% endfor %}
    </select>
{% endif %}
```

## Getting One Event Type

`craft.events.getEventTypeByHandle(handle)` returns the event type with the given handle, or `null` if there isn't one. `handle` is required. Use it when a template is built for one type, such as a Conferences landing page.

```twig
{% set eventType = craft.events.getEventTypeByHandle('conferences') %}

{% if eventType %}
    <h1>{{ eventType.name }}</h1>
{% endif %}
```

`craft.events.getEventTypeById(id)` works the same way with an event type's ID.

## Finding Ticket Types

Ticket types, such as Adult and Child, belong to an individual event rather than being shared across the site. Fetch them with a [ticket type query](docs:getting-elements/ticket-type-queries), or with `event.getTicketTypes()` on an event.

```twig
{% set ticketTypes = craft.events.ticketTypes()
    .event(event)
    .all() %}
```
