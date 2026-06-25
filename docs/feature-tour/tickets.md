# Tickets
Once you’ve created your [Sessions](docs:feature-tour/sessions) and [Ticket Types](docs:feature-tour/ticket-types) for an event, you can generate **Tickets**. Tickets are actual, [purchasable](https://craftcms.com/docs/commerce/5.x/system/purchasables.html) items in Craft Commerce, and are what you add to your cart.

To handle recurring sessions, Tickets are automatically generated based on your Sessions and Ticket Types. As such, you don't need to manage them directly.

## Ticket Generation
When viewing an event in the control panel, and you have at least one Session and one Ticket Type, the **Ticket Status** panel in the sidebar shows whether your generated tickets are in sync.

Whenever you add or remove a Session or Ticket Type, the panel will notify you that ticket updates are pending. Click **Apply ticket updates** to queue a sync job. While the job runs, the panel shows live progress including the current step and a progress bar.

Ticket updates are processed in Craft’s queue, so larger events won’t block the control panel while tickets are being created or removed.

Changing element attributes such as Session dates or Ticket Type pricing doesn't require tickets to be regenerated, as they are dynamically resolved.

### Auto-apply
By default, ticket updates must be applied manually. To automatically queue updates when saving an event with pending changes, enable **Apply Pending Ticket Updates** under Settings → Events → Tickets, or set `applyPendingTicketUpdates` to `true` in your [config file](docs:get-started/configuration).

## Title and SKU
A ticket’s title and SKU is automatically generated based on your Event Type’s **Ticket Title Format** and **Ticket SKU Format** settings.

## Purchased Ticket
Once a Ticket has been added to a cart, and the user completes checkout, these tickets are converted to a [Purchased Ticket](docs:developers/purchased-ticket) which differentiate between a Ticket available for sale, and one that's been reserved.

## Stock
Ticket stock is calculated from the most restrictive remaining capacity available to the generated ticket. That can include the ticket type, the ticket’s session, and the parent event.

See [Capacity](docs:feature-tour/capacity) for the full calculation rules and multi-session behavior.