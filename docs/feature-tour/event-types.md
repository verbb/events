# Event Types

An Event Type defines the settings and field layouts shared by a kind of event. For example, a Workshops type can provide fields for an instructor and required materials, while a Concerts type can provide an artist lineup. Each event uses one of these types.

The type also defines the layouts for its sessions and ticket types. This lets you decide which information editors should supply at each level: the workshop description belongs to the event, its date belongs to a session, and the admission price belongs to a ticket type. [Creating Your First Event](docs:get-started/creating-your-first-event) shows how these parts produce a ticket someone can purchase.

## Event Fields
You can define what sort of fields should be available to your events.

Mandatory fields are:
- Title
- Sessions
- Ticket Types

## Session Fields
You can define what sort of fields should be available to your sessions.

Mandatory fields are:
- Start Date
- End Date
- All Day
- Frequency

## Ticket Type Fields
You can define what sort of fields should be available to your ticket types.

Mandatory fields are:
- Title
- Price
- Capacity
