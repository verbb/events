# Interfaces & Types

Each element type Events adds has a GraphQL interface, which every query on the [Queries](docs:graphql/queries) page returns. Along with the fields below, each interface has [Craft’s element fields](https://craftcms.com/docs/5.x/development/graphql.html#interfaces), such as `id`, `uid`, `title`, `slug`, `url`, `enabled`, `status`, `dateCreated` and `dateUpdated`.

Events also creates a concrete type for each event type, named after the event type’s handle, such as `conference_Event`, `conference_Session`, `conference_TicketType`, `conference_Ticket` and `conference_PurchasedTicket`. These types add the custom fields from the event type’s field layouts. Request custom fields with an inline fragment on the concrete type.

```graphql
{
  eventsEvents(type: "conference") {
    title
    ... on conference_Event {
      venue
      summary
    }
  }
}
```

Use `__typename` to see which concrete type an element is.

## Events

### The `EventInterface` Interface

The interface implemented by every Event element.

::: reference
### `capacity`

**Type:** `Int`

The number of seats shared across all of the event’s sessions and ticket types. This is `null` when the event has no overall limit. See [Capacity](docs:feature-tour/capacity) for how the limits combine.
:::

::: reference
### `endDate`

**Type:** `DateTime`

The date and time the event ends, which is the end date of its last enabled session. This is `null` when the event has no sessions.
:::

::: reference
### `eventTypeHandle`

**Type:** `String`

The handle of the event type the event belongs to.
:::

::: reference
### `eventTypeId`

**Type:** `Int`

The ID of the event type the event belongs to.
:::

::: reference
### `purchasedTickets`

**Type:** `[PurchasedTicketInterface]`

The tickets customers have bought for the event. This field is only in the schema when it has a purchased tickets permission, and only returns purchased tickets for event types it has both permissions for.
:::

::: reference
### `sessions`

**Type:** `[SessionInterface]`

The event’s enabled sessions, in the order they appear on the event.
:::

::: reference
### `startDate`

**Type:** `DateTime`

The date and time the event starts, which is the start date of its first enabled session. This is `null` when the event has no sessions.
:::

::: reference
### `ticketTypes`

**Type:** `[TicketTypeInterface]`

The event’s enabled ticket types, in the order they appear on the event.
:::

::: reference
### `tickets`

**Type:** `[TicketInterface]`

The event’s enabled tickets, one for each combination of session and ticket type.
:::

This example fetches an event with its sessions and the tickets a customer can buy.

```graphql
{
  eventsEvent(slug: "craft-summit-2026") {
    title
    capacity
    sessions {
      title
      startDate
    }
    tickets {
      id
      title
      price
    }
  }
}
```

## Sessions

### The `SessionInterface` Interface

The interface implemented by every session.

::: reference
### `allDay`

**Type:** `Boolean`

Whether the session runs all day, rather than between set times.
:::

::: reference
### `capacity`

**Type:** `Int`

The number of seats shared by every ticket type in the session. This is `null` when the session has no limit of its own.
:::

::: reference
### `endDate`

**Type:** `DateTime`

The date and time the session ends.
:::

::: reference
### `event`

**Type:** `EventInterface`

The event the session belongs to. This is `null` when the schema can’t read the event’s type.
:::

::: reference
### `startDate`

**Type:** `DateTime`

The date and time the session starts.
:::

```graphql
{
  eventsSessions(eventId: 1024) {
    title
    startDate @formatDateTime(format: "D j M, g:ia")
    allDay
    event {
      title
    }
  }
}
```

## Ticket Types

### The `TicketTypeInterface` Interface

The interface implemented by every ticket type.

::: reference
### `capacity`

**Type:** `Int`

The number of seats that can be sold for this ticket type in each session. This is `null` when the ticket type has no limit of its own.
:::

::: reference
### `event`

**Type:** `EventInterface`

The event the ticket type belongs to. This is `null` when the schema can’t read the event’s type.
:::

::: reference
### `price`

**Type:** `Float`

The price of a ticket of this type.
:::

```graphql
{
  eventsTicketTypes(eventId: 1024) {
    title
    price
    capacity
  }
}
```

## Tickets

### The `TicketInterface` Interface

The interface implemented by every ticket. A ticket is the purchasable for one ticket type in one session.

::: reference
### `event`

**Type:** `EventInterface`

The event the ticket belongs to. This is `null` when the schema can’t read the event’s type.
:::

::: reference
### `price`

**Type:** `Float`

The price of the ticket.
:::

::: reference
### `session`

**Type:** `SessionInterface`

The session the ticket is for. This is `null` when the schema can’t read it.
:::

::: reference
### `sku`

**Type:** `String`

The ticket’s SKU, used to identify it in Commerce.
:::

::: reference
### `type`

**Type:** `TicketTypeInterface`

The ticket type the ticket is for. This is `null` when the schema can’t read it.
:::

```graphql
{
  eventsTickets(eventId: 1024) {
    id
    sku
    price
    session {
      title
    }
    type {
      title
    }
  }
}
```

## Purchased Tickets

### The `PurchasedTicketInterface` Interface

The interface implemented by every purchased ticket. It’s only available to schemas with a purchased tickets permission, described in [Configure Schema Access](docs:graphql/queries#configure-schema-access).

Its `uid` and `title` fields return `null` unless the schema also has the check-in permission for the event’s type, because the `uid` identifies the ticket for check-in and the title can include it.

::: reference
### `cancelledAt`

**Type:** `DateTime`

The date and time the reservation was cancelled. This is `null` for an active reservation.
:::

::: reference
### `cancelledReason`

**Type:** `String`

The reason given when the reservation was cancelled. This is `null` for an active reservation, or when no reason was given.
:::

::: reference
### `checkedIn`

**Type:** `Boolean`

Whether the ticket holder has checked in.
:::

::: reference
### `checkedInDate`

**Type:** `DateTime`

The date and time the ticket holder checked in. This is `null` until they check in.
:::

::: reference
### `event`

**Type:** `EventInterface`

The event the ticket was bought for. This is `null` when the schema can’t read it.
:::

::: reference
### `isActive`

**Type:** `Boolean`

Whether the reservation is active, meaning it holds a place at the event.
:::

::: reference
### `isCancelled`

**Type:** `Boolean`

Whether the reservation has been cancelled.
:::

::: reference
### `reservationStatus`

**Type:** `String`

The reservation’s status, which is `active` or `cancelled`.
:::

::: reference
### `session`

**Type:** `SessionInterface`

The session the ticket was bought for. This is `null` when the schema can’t read it.
:::

::: reference
### `ticket`

**Type:** `TicketInterface`

The ticket that was bought. This is `null` when the schema can’t read it.
:::

::: reference
### `ticketType`

**Type:** `TicketTypeInterface`

The ticket type that was bought. This is `null` when the schema can’t read it.
:::

```graphql
{
  eventsPurchasedTickets(eventId: 1024) {
    id
    reservationStatus
    checkedIn
    checkedInDate
    ticketType {
      title
    }
  }
}
```
