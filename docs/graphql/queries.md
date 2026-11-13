# Queries

Events adds GraphQL queries for events, sessions, ticket types, tickets and purchased tickets, so a headless front end can list upcoming events and what’s on sale. Each element type has a query for a list, a query for a single element and a query for a count.

## Configure Schema Access

In Craft’s **GraphQL → Schemas**, edit the schema your client uses. Events adds two groups of permissions, each with an option per event type.

- Under **Events**, enable **View “Conference” events** for each event type the schema should read. This gives access to that event type’s events, and the sessions, ticket types and tickets that belong to them.
- Under **Purchased Tickets**, enable **View “Conference” purchased tickets** to give access to the attendee records for that event type’s events. The schema also needs the event type’s **Events** permission. Purchased tickets hold customer details, so only grant this to private schemas.
- Also under **Purchased Tickets**, enable **View “Conference” purchased ticket check-in identifiers and titles** when the client needs each purchased ticket’s `uid` or `title`. The `uid` is what a check-in URL uses, so without this permission both fields return `null`, and the query can’t filter or sort purchased tickets by `uid`.

Follow [Craft’s GraphQL setup](https://craftcms.com/docs/5.x/development/graphql.html) to create an endpoint and choose a public schema or a token for a private schema. Test your queries in GraphiQL using the same schema, because the administrator’s full schema can hide a missing permission. If a query isn’t listed in the schema, or returns nothing, check the schema has the event type’s permission.

The examples on this page use an event type with the handle `conference`. Fields on the returned elements are described in [Interfaces & Types](docs:graphql/interfaces-types).

## Event Queries

### The `eventsEvents` Query

Returns a list of Event elements. Use it to show an events listing or calendar.

Along with [Craft’s element query arguments](https://craftcms.com/docs/5.x/development/graphql.html#element-query-arguments), such as `id`, `slug`, `site`, `limit` and `orderBy`, and an argument for each custom field in your event types’ field layouts, it accepts the following arguments. They match the [event query parameters](docs:getting-elements/event-queries).

| Argument | Type | Description |
| --- | --- | --- |
| `endDate` | `DateTime` | Narrows the results by the event’s end date, which is the end date of its last session. |
| `startDate` | `DateTime` | Narrows the results by the event’s start date, which is the start date of its first session. |
| `type` | `[String]` | Narrows the results to event types with these handles. |
| `typeId` | `[QueryArgument]` | Narrows the results to event types with these IDs. |

Unlike `craft.events.events()` in Twig, GraphQL doesn’t hide past events by default. Pass an `endDate` to only fetch current and upcoming events.

```graphql
{
  eventsEvents(type: "conference", endDate: ">= 2026-11-13", orderBy: "startDate asc", limit: 10) {
    id
    title
    url
    startDate @formatDateTime(format: "j M Y")
    endDate @formatDateTime(format: "j M Y")
    eventTypeHandle
  }
}
```

```json
{
  "data": {
    "eventsEvents": [
      {
        "id": "1024",
        "title": "Craft Summit 2026",
        "url": "https://example.com/events/craft-summit-2026",
        "startDate": "4 Dec 2026",
        "endDate": "5 Dec 2026",
        "eventTypeHandle": "conference"
      }
    ]
  }
}
```

### The `eventsEvent` Query

Returns a single Event element, or `null` when none match. It takes the same arguments as `eventsEvents`. Use it on an event’s own page, fetching it by its slug.

```graphql
query Event($slug: [String]) {
  eventsEvent(slug: $slug) {
    title
    startDate
    sessions {
      title
      startDate
      endDate
    }
    ticketTypes {
      title
      price
    }
  }
}
```

```json
{
  "slug": "craft-summit-2026"
}
```

```json
{
  "data": {
    "eventsEvent": {
      "title": "Craft Summit 2026",
      "startDate": "2026-12-04T09:00:00+10:00",
      "sessions": [
        {
          "title": "Day One",
          "startDate": "2026-12-04T09:00:00+10:00",
          "endDate": "2026-12-04T17:00:00+10:00"
        },
        {
          "title": "Day Two",
          "startDate": "2026-12-05T09:00:00+10:00",
          "endDate": "2026-12-05T17:00:00+10:00"
        }
      ],
      "ticketTypes": [
        {
          "title": "General Admission",
          "price": 199
        },
        {
          "title": "Student",
          "price": 99
        }
      ]
    }
  }
}
```

### The `eventsEventCount` Query

Returns the number of Event elements that match the arguments. It takes the same arguments as `eventsEvents`.

```graphql
{
  eventsEventCount(type: "conference")
}
```

```json
{
  "data": {
    "eventsEventCount": 12
  }
}
```

## Session Queries

### The `eventsSessions` Query

Returns a list of sessions. Use it to build a timetable across events, such as every session this month.

Along with Craft’s element query arguments and an argument for each custom field in your sessions’ field layouts, it accepts the following arguments. They match the [session query parameters](docs:getting-elements/session-queries).

| Argument | Type | Description |
| --- | --- | --- |
| `capacity` | `[QueryArgument]` | Narrows the results by the session’s shared capacity. |
| `endDate` | `DateTime` | Narrows the results by the session’s end date. |
| `eventId` | `[QueryArgument]` | Narrows the results to sessions of the events with these IDs. |
| `startDate` | `DateTime` | Narrows the results by the session’s start date. |

```graphql
{
  eventsSessions(startDate: [">= 2026-12-01", "< 2027-01-01"], orderBy: "startDate asc") {
    title
    startDate
    endDate
    event {
      title
      url
    }
  }
}
```

```json
{
  "data": {
    "eventsSessions": [
      {
        "title": "Day One",
        "startDate": "2026-12-04T09:00:00+10:00",
        "endDate": "2026-12-04T17:00:00+10:00",
        "event": {
          "title": "Craft Summit 2026",
          "url": "https://example.com/events/craft-summit-2026"
        }
      }
    ]
  }
}
```

### The `eventsSession` Query

Returns a single session, or `null` when none match. It takes the same arguments as `eventsSessions`.

```graphql
{
  eventsSession(id: 2051) {
    title
    startDate
    endDate
    allDay
    capacity
  }
}
```

```json
{
  "data": {
    "eventsSession": {
      "title": "Day One",
      "startDate": "2026-12-04T09:00:00+10:00",
      "endDate": "2026-12-04T17:00:00+10:00",
      "allDay": false,
      "capacity": 150
    }
  }
}
```

### The `eventsSessionCount` Query

Returns the number of sessions that match the arguments. It takes the same arguments as `eventsSessions`.

```graphql
{
  eventsSessionCount(eventId: 1024)
}
```

```json
{
  "data": {
    "eventsSessionCount": 2
  }
}
```

## Ticket Type Queries

### The `eventsTicketTypes` Query

Returns a list of ticket types, such as General Admission or Student.

Along with Craft’s element query arguments and an argument for each custom field in your ticket types’ field layouts, it accepts the following arguments. They match the [ticket type query parameters](docs:getting-elements/ticket-type-queries).

| Argument | Type | Description |
| --- | --- | --- |
| `capacity` | `Int` | Narrows the results by the ticket type’s capacity. |
| `eventId` | `[QueryArgument]` | Narrows the results to ticket types of the events with these IDs. |
| `price` | `Float` | Narrows the results by the ticket type’s price. |

```graphql
{
  eventsTicketTypes(eventId: 1024) {
    title
    price
    capacity
  }
}
```

```json
{
  "data": {
    "eventsTicketTypes": [
      {
        "title": "General Admission",
        "price": 199,
        "capacity": 120
      },
      {
        "title": "Student",
        "price": 99,
        "capacity": 30
      }
    ]
  }
}
```

### The `eventsTicketType` Query

Returns a single ticket type, or `null` when none match. It takes the same arguments as `eventsTicketTypes`.

```graphql
{
  eventsTicketType(id: 3001) {
    title
    price
    event {
      title
    }
  }
}
```

```json
{
  "data": {
    "eventsTicketType": {
      "title": "General Admission",
      "price": 199,
      "event": {
        "title": "Craft Summit 2026"
      }
    }
  }
}
```

### The `eventsTicketTypeCount` Query

Returns the number of ticket types that match the arguments. It takes the same arguments as `eventsTicketTypes`.

```graphql
{
  eventsTicketTypeCount(eventId: 1024)
}
```

```json
{
  "data": {
    "eventsTicketTypeCount": 2
  }
}
```

## Ticket Queries

### The `eventsTickets` Query

Returns a list of tickets. A ticket is what a customer adds to the cart, combining one session with one ticket type, so use the ticket’s `id` as the purchasable ID when adding it to a Commerce cart.

Along with Craft’s element query arguments, it accepts the following arguments. They match the [ticket query parameters](docs:getting-elements/ticket-queries).

| Argument | Type | Description |
| --- | --- | --- |
| `eventId` | `[QueryArgument]` | Narrows the results to tickets for the events with these IDs. |
| `price` | `Float` | Narrows the results by the ticket’s price. |
| `sessionId` | `[QueryArgument]` | Narrows the results to tickets for the sessions with these IDs. |
| `sku` | `String` | Narrows the results by the ticket’s SKU. |

```graphql
{
  eventsTickets(eventId: 1024) {
    id
    title
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

```json
{
  "data": {
    "eventsTickets": [
      {
        "id": "4101",
        "title": "General Admission - Day One",
        "sku": "SUMMIT-GA-1",
        "price": 199,
        "session": {
          "title": "Day One"
        },
        "type": {
          "title": "General Admission"
        }
      }
    ]
  }
}
```

### The `eventsTicket` Query

Returns a single ticket, or `null` when none match. It takes the same arguments as `eventsTickets`.

```graphql
{
  eventsTicket(sku: "SUMMIT-GA-1") {
    id
    title
    price
  }
}
```

```json
{
  "data": {
    "eventsTicket": {
      "id": "4101",
      "title": "General Admission - Day One",
      "price": 199
    }
  }
}
```

### The `eventsTicketCount` Query

Returns the number of tickets that match the arguments. It takes the same arguments as `eventsTickets`.

```graphql
{
  eventsTicketCount(eventId: 1024)
}
```

```json
{
  "data": {
    "eventsTicketCount": 4
  }
}
```

## Purchased Ticket Queries

Purchased ticket queries need the **View “Conference” purchased tickets** permission described in [Configure Schema Access](#configure-schema-access), and only return purchased tickets for event types the schema has both permissions for. The `purchasedTickets` field on events follows the same rule.

### The `eventsPurchasedTickets` Query

Returns a list of purchased tickets, which are the tickets customers have bought. Use it for an attendee list or a check-in app.

Along with Craft’s element query arguments, it accepts the following arguments. They match the [purchased ticket query parameters](docs:getting-elements/purchased-ticket-queries).

| Argument | Type | Description |
| --- | --- | --- |
| `checkedIn` | `Boolean` | Narrows the results by whether the ticket holder has checked in. |
| `checkedInDate` | `DateTime` | Narrows the results by when the ticket holder checked in. |
| `eventId` | `[QueryArgument]` | Narrows the results to purchased tickets for the events with these IDs. |
| `reservationStatus` | `[QueryArgument]` | Narrows the results to `active` or `cancelled` reservations. |
| `sessionId` | `[QueryArgument]` | Narrows the results to purchased tickets for the sessions with these IDs. |
| `ticketId` | `[QueryArgument]` | Narrows the results to purchased tickets bought from the tickets with these IDs. |
| `ticketTypeId` | `[QueryArgument]` | Narrows the results to purchased tickets of the ticket types with these IDs. |

```graphql
{
  eventsPurchasedTickets(eventId: 1024, reservationStatus: "active", checkedIn: false) {
    id
    checkedIn
    reservationStatus
    session {
      title
    }
    ticketType {
      title
    }
  }
}
```

```json
{
  "data": {
    "eventsPurchasedTickets": [
      {
        "id": "5230",
        "checkedIn": false,
        "reservationStatus": "active",
        "session": {
          "title": "Day One"
        },
        "ticketType": {
          "title": "General Admission"
        }
      }
    ]
  }
}
```

### The `eventsPurchasedTicket` Query

Returns a single purchased ticket, or `null` when none match. It takes the same arguments as `eventsPurchasedTickets`. Fetching a purchased ticket by `uid`, or requesting its `uid` or `title`, needs the check-in permission.

```graphql
query PurchasedTicket($uid: [String]) {
  eventsPurchasedTicket(uid: $uid) {
    title
    checkedIn
    checkedInDate
    isCancelled
  }
}
```

```json
{
  "uid": "6f1c2b0e-4a2d-4a36-9a7e-2d8c1f3b9e10"
}
```

```json
{
  "data": {
    "eventsPurchasedTicket": {
      "title": "Craft Summit 2026 - General Admission - Day One",
      "checkedIn": true,
      "checkedInDate": "2026-12-04T08:47:12+10:00",
      "isCancelled": false
    }
  }
}
```

### The `eventsPurchasedTicketCount` Query

Returns the number of purchased tickets that match the arguments. It takes the same arguments as `eventsPurchasedTickets`. Use it to show how many attendees have checked in.

```graphql
{
  eventsPurchasedTicketCount(eventId: 1024, checkedIn: true)
}
```

```json
{
  "data": {
    "eventsPurchasedTicketCount": 87
  }
}
```
