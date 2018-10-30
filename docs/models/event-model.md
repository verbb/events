# Event Model

When you're on a single event template, or looping through events using `craft.events.events()`, you're actually working with a `Events_EventModel`. This in turn extends Craft's [Element Criteria Model](https://craftcms.com/docs/templating/elementcriteriamodel) object.

## Simple Output

Outputting a `Events_EventModel` object in your template without attaching a property or method will return the event’s name:

`<h1>{{ event }}</h1>`

Event Models have the following attributes and methods:

## Attributes

### title

The events name/title.

### id

The id of the event.

### eventType

The event's event type.

### typeId

The event's event type id.

### status

live, pending or expired based on startDate and endDate dates. Pending are events with a future startDate date.

### enabled

true or false

### isEditable

true or false

### tickets

A list of event's [Ticket Models](/craft-plugins/events/docs/models/ticket-model)

### cpEditUrl

The url to edit this event.

### urlFormat

The url format of this event

### allDay

Ether true or false if the event is an all day event and no specific start and end time is set.

### capacity

The total capacity of tickets available for this event.

### startDate

The events start date

### endDate

The events end date