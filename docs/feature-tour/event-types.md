# Event Types

Event types act to group your events, and depending on your needs, you may only require a single event type. This is where you also define the templates and URL structure used by events. Custom fields can be added to event types, providing each individual event additional fields.

Go to the main section for Events in your control panel main menu, and select **Event Types**. This will list all the event types you've created.

![Event Types Overview](/docs/screenshots/event-types-overview.png)

The **Delete** icon deletes already created event types. Created events for this event type will also be deleted. Already purchased tickets still remain in the database.

### Create a Event Type

Each field is fairly self-explanatory, but any additional information is provided below.

![Event Types Edit](/docs/screenshots/event-types-edit.png)

- **Name**: What this event type will be called in the CP.
- **Handle**: How you’ll refer to this event type in the templates.

If you ticked **Events of this type have their own URLs**, the following fields appear:

- **Event URL Format**: What the event URLs should look like. You can include tags that output event properties, such as such as {slug} or {publishDate|date("Y")}.
- **Event Template**: The template to use when a event’s URL is requested.

Be sure to check out our [Template Guide →](docs:template-guides/events-index) to get started quickly to show events.