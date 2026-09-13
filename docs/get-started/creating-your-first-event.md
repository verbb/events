# Creating Your First Event

This walkthrough connects the parts of a ticketed event using a pottery workshop. Start with Events and Craft Commerce installed, access to configure Events, and a working Commerce checkout. The plugin supplies purchasable tickets; your Commerce templates and payment gateway handle the checkout.

## Define the Kind of Event

Create a **Workshops** event type in Events' settings. An [Event Type](docs:feature-tour/event-types) defines the field layouts and formats shared by events of that kind. It is configuration, rather than an individual date someone can book.

Create an event using that type and name it **Introduction to Pottery**. This Event holds the workshop's content and groups its sessions and ticket types. Add the descriptive fields your visitors need, such as what to bring and where the workshop takes place.

## Add a Session and Ticket Type

Add a [Session](docs:feature-tour/sessions) with a future start and end time. Choose a single occurrence for this first test. A session is when the workshop happens; adding another date later creates another booking opportunity for the same workshop.

Add a **General Admission** [Ticket Type](docs:feature-tour/ticket-types), with a price appropriate to your test store's currency. Set the session's capacity to 12 for a room with 12 seats. Leave broader limits blank unless you intend them to apply. A blank capacity means no limit at that level, while zero means no availability. The [capacity explanation](docs:feature-tour/capacity) covers how limits combine when you add more sessions or ticket types.

Save the event and inspect its **Ticket Status** panel. Apply pending ticket updates and let Craft's queue process the sync. Events creates a [Ticket](docs:feature-tour/tickets) for the session and ticket-type combination. That generated Ticket is the purchasable item your checkout adds to a cart.

## Display and Purchase a Ticket

Use the [Single Event template](docs:template-guides/single-event) to connect the workshop page to its tickets, and the [Cart and Order template](docs:template-guides/cart-order) to display the purchase. Adapt the sample handles and template paths to the project; the event type's template settings must point to the template you create.

Visit the workshop page and check its title, session date, price and availability. Add one ticket and complete checkout through your test gateway. Inspect the completed order and its [Purchased Ticket](docs:developers/purchased-ticket). A Purchased Ticket represents the booked admission, whereas the generated Ticket remains the item other customers can buy.

## Check the Attendee's Ticket

Configure a [PDF ticket](docs:feature-tour/pdf-ticket) if attendees should receive or download one. Check the attendee's name, event details and session time in the result. Follow the [check-in instructions](docs:feature-tour/ticket-check-in) using this test purchase and confirm that checking in the same ticket again reports that it has already been used.

Finally, return to the event and inspect its availability. The completed booking should be reflected in the remaining capacity. Once this single-session path works, add a second workshop date or another ticket type and check the generated combinations before accepting bookings.
