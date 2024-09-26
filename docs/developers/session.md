# Session
Whenever you’re dealing with a session in your template, you’re working with a `Session` object.

## Attributes

Attribute | Description
--- | ---
`id` | The ID of the session.
`event` | The associated [Event](docs:developers/event).
`eventId` | The ID of the event this session belongs to.
`title` | The title of the session (auto-generated based on the Event Type’s settings).
`startDate` | The date and time the session starts.
`endDate` | The date and time the session ends.
`isAllDay` | Whether this session spans the entire day (true or false).
`status` | The status of the session (e.g., `live`, `pending`, or `expired`).

## Methods

Method | Description
--- | ---
`getCpEditUrl()` | Returns the URL to edit this session in the control panel.
`getTickets()` | Returns a collection of [Ticket](docs:developers/ticket) objects for this session.
