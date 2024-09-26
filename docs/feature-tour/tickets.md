# Tickets
Once you’ve created your [Sessions](docs:feature-tour/sessions) and [Ticket Types](docs:feature-tour/ticket-types) for an event, you can generate **Tickets**. Tickets are actual, [purchasable](https://craftcms.com/docs/commerce/5.x/system/purchasables.html) items in Craft Commerce, and are what you add to your cart.

To handle recurring sessions, Tickets are automatically generated based on your Sessions and Ticket Types. As such, you don't need to manage them directly.

## Ticket Generation
When viewing an event in the control panel, and have at least one Session and one Ticket Type, you'll see the option to generate Tickets.

Whenever you add or delete a Session or Ticket Type, this same notification will let you know if tickets need to be regenerated to reflect your changed elements.

Changing element attributes such as Session dates or Ticket Type pricing doesn't require tickets to be regenerated, as they are dynamically resolved.

## Title and SKU
A ticket’s title and SKU is automatically generated based on your Event Type’s **Ticket Title Format** and **Ticket SKU Format** settings.

## Purchased Ticket
Once a Ticket has been added to a cart, and the user completes checkout, these tickets are converted to a [Purchased Ticket](docs:feature-tour/purchased-ticket) which differentiate between a Ticket available for sale, and one that's been reserved.