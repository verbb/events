# Event

Whenever you're dealing with an event in your template, you're actually working with a `Event` object.

## Attributes

Attribute | Description
--- | ---
`id` | ID of the event.
`title` | The events name/title.
`url` | The URL to this single event.
`type` | The event's event type.
`typeId` | The event's event type id.
`status` | live, pending or expired based on startDate, endDate, postDate and expiryDate dates.
`enabled` | true or false.
`isAvailable` | Whether this event is available for purchase. This will only be false if there are no tickets available for sale, therefore the event is completely sold out.
`allDay` | Either true or false if the event is an all day event and no specific start and end time is set.
`capacity` | The total capacity of tickets available for this event.
`startDate` | The events start date
`endDate` | The events end date
`postDate` | The date this event is available for sale.
`expiryDate` | The date this event will no longer be available for sale.

## Methods

Method | Description
--- | ---
`getCpEditUrl()` | The url to edit this event in the control panel.
`getTickets()` | A list of event's [Ticket's](docs:developers/ticket)
`getAvailableTickets()` | A list of event's available [Ticket's](docs:developers/ticket) for sale. These take into account the 'Available To/From' fields for each ticket, along with ticket capacity and sales.
