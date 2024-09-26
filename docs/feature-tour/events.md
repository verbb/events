# Events
At the core of the plugin is the **Event** element. Think of an event as the main container for everything related to your gathering, from dates and sessions to tickets and check-ins. If you’re organizing a concert, a conference, or even a single workshop, that’s an Event.

Events can have one or more [Sessions](docs:feature-tour/sessions) which define the date(s) of the event, and one or more [Ticket Types](docs:feature-tour/ticket-types) which define tickets, pricing, and capacities.

In the same way that Craft’s native element types each share a set of common attributes, every event has a **Title**, **Slug**, **Post Date**, **Expiry Date**, and per-site status options.

## Event Capacity
While each [Ticket Type](docs:feature-tour/ticket-types) sets its own capacity (e.g., how many VIP tickets or General Admission tickets are available), you can also override the overall capacity at the Event level. This is useful if you have a hard limit on the number of attendees, regardless of how many tickets or types you’re selling.

By default, the event’s capacity is calculated by summing the total capacities of all enabled ticket types. But if you want to enforce a strict maximum, you can manually set the **Event Capacity**.

Let’s say you have an event with 200 General Admission tickets and 50 VIP tickets. By default, the event capacity will be set to 250 (200 + 50). However, if the venue can only hold 230 people, you could override the capacity at the event level to ensure you don’t oversell.

Once the overall capacity is hit, tickets will no longer be available for purchase, even if individual ticket types still have availability.

## Ticket Status
Ticket generation in this plugin is a dynamic process. Whenever you add or remove [Sessions](docs:feature-tour/sessions) or [Ticket Types](docs:feature-tour/ticket-types), the **Ticket Status** will notify you if the tickets need to be regenerated. Making changes like pricing, capacity or start/end dates **does not** require you to regenerate tickets, as that's all dynamic.
