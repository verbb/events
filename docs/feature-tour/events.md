# Events

Events manage the tickets your customers can purchase. Tickets are very similar to Commerce Products, and in fact are [purchasables](https://craftcommerce.com/docs/purchasables), allowing it to make use of many core Commerce functions.

### Create an Event

Go to the main section for Events in your control panel main menu. This will list all the events you've created.

:::tip
Before you can start creating events, you'll first need to setup a [Event Type →](docs:feature-tour/ticket-types)
:::

Each field is fairly self-explanatory, but any additional information is provided below.

![Events Edit 3](/docs/screenshots/events-edit-3.png)

- **Title**: Set up a title for your event to easily find it in the CP and display on the front-end.
- **Start Date**: The event start date.
- **End Date**: The event end date.
- **All Day Event**: Whether this event should span the whole day. This will automatically set the start and end date times to midnight on the respective days.
- **Slug**: The slug for this event.
- **Enable**: Enable or disable this event.
- **Delete**: The delete button deletes the event. Already purchased tickets for this event remain in the database.

:::tip
Any custom fields you've added to an Event Type will appear as tabs preceding the `Dates/Times` tab.
:::

![Event Tickets 2](/docs/screenshots/event-tickets-2.png)

- **Total Capacity**: The total capacity controls the total available tickets for this event. You can control this for the entire event, or per-ticket.
- **Ticket Type**: Select a ticket type this ticket should be associated with.
- **Quantity**: Set a quantity for this ticket type.
- **Price**: Specify a price for this ticket type.
- **Available From**: Optionally set a date from which this ticket can only be purchased after.
- **Available To**: Optionally set a date from which this ticket can only be purchased until.
- **Delete Ticket Type**: You can remove this ticket from the event. Already purchased tickets remain in the database.

:::tip
Any custom fields you've added to a Ticket Type will appear as fields below the `Available To` field.
:::