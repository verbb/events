# Event
Whenever you’re dealing with an event in your template, you’re actually working with a `Event` object.

## Attributes

Attribute | Description
--- | ---
`id` | The ID of the event.
`title` | The event’s title.
`url` | The URL to this single event.
`type` | The event’s type (as defined by its event type).
`typeId` | The ID of the event’s type.
`status` | The current status of the event: `live`, `pending`, or `expired`, determined based on `startDate`, `endDate`, `postDate`, and `expiryDate`.
`enabled` | Whether the event is enabled (`true` or `false`).
`isAvailable` | Indicates if the event is available for purchase. This will be `false` if there are no tickets available for sale, meaning the event is completely sold out.
`capacity` | The total capacity of tickets available for this event.
`startDate` | The start date of the event (based on the first session).
`endDate` | The end date of the event (based on the last session).
`postDate` | The date when this event becomes available (i.e., when it’s posted).
`expiryDate` | The date after which this event will no longer be available.

## Methods

Method | Description
--- | ---
`getCpEditUrl()` | Returns the URL to edit this event in the control panel.
`getSessions()` | Returns a collection of [Session](docs:developers/session) objects associated with this event.
`getTicketTypes()` | Returns a collection of [TicketType](docs:developers/ticket-type) objects associated with this event.
`getTickets()` | Returns a collection of [Ticket](docs:developers/ticket) objects generated for this event.
`getAvailableTickets()` | Returns a collection of available [Ticket](docs:developers/ticket) objects for sale. This respects the 'Available From/To' dates, along with ticket capacity and sales status.
`getIcsUrl()` | Returns a URL to download the ICS (iCalendar) file for this single event.
