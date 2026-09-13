# Events
At the core of the plugin is the **Event** element. Think of an event as the main container for everything related to your gathering, from dates and sessions to tickets and check-ins. If you’re organising a concert, a conference, or even a single workshop, that’s an Event.

Events can have one or more [Sessions](docs:feature-tour/sessions) which define the date(s) of the event, and one or more [Ticket Types](docs:feature-tour/ticket-types) which define tickets, pricing, and capacities.

In the same way that Craft’s native element types each share a set of common attributes, every event has a **Title**, **Slug**, **Post Date**, **Expiry Date**, and per-site status options.

## Event Capacity
While each [Ticket Type](docs:feature-tour/ticket-types) sets its own capacity (e.g., how many VIP tickets or General Admission tickets are available), you can also set a shared capacity at the Event level. This is useful if you have a hard limit on the total number of attendees, regardless of how many sessions or ticket types you’re selling.

A blank Event Capacity does not enforce an event-wide limit. Ticket type and session limits still apply. Set Event Capacity when every session should draw from one shared allowance.

For example, a workshop has Saturday and Sunday sessions, each limited to 20 attendees. Leave Event Capacity blank to allow 20 attendees on each day. Set it to 30 if only 30 bookings can be accepted across both days, even though each session has space for 20.

Once the overall capacity is hit, tickets will no longer be available for purchase, even if individual ticket types or sessions still have availability.

For multi-session events, event capacity is shared across all sessions. Leave it blank if each session should manage its own capacity. See [Capacity](docs:feature-tour/capacity) for the full calculation rules.

## Ticket Status
Ticket generation in this plugin is a dynamic process. Whenever you add or remove [Sessions](docs:feature-tour/sessions) or [Ticket Types](docs:feature-tour/ticket-types), the **Ticket Status** panel in the event sidebar will notify you that ticket updates are pending.

Click **Apply ticket updates** to queue a sync. The panel then shows live progress while Craft generates or removes the corresponding [Tickets](docs:feature-tour/tickets). Making changes like pricing, capacity or start/end dates **does not** require you to apply ticket updates, as that's all dynamic.

You can optionally enable **Apply Pending Ticket Updates** in Settings → Events → Tickets to queue updates automatically when saving an event with pending changes.
