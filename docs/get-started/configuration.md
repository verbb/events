# Configuration

You can customise Events’s settings using a PHP configuration file. This is optional: each setting has a default, so you only need to include the values you want to change.

To override a setting, create `events.php` in your Craft project’s `/config` directory and return an array of setting names and values. For example, the following will change the name displayed in the control panel:

```php
<?php

return [
    'pluginName' => 'Events Tools',
];
```

All other settings keep their defaults. Add any further settings you want to change to the same array. The options below explain the available settings and their defaults.

## Configuration Options

::: reference
### `pluginName`

**Type:** `string` · **Default:** `'Events'`

Change the plugin name.
:::


::: reference
### `defaultEventIndexStatus`

**Type:** `string` · **Default:** `''`

Set the default status filter for the Events index. Leave blank to show all statuses.
:::


::: reference
### `ticketPdfPath`

**Type:** `string` · **Default:** `'shop/_pdf/tickets'`

Set the path to your PDF.
:::


::: reference
### `ticketPdfFilenameFormat`

**Type:** `string` · **Default:** `'Tickets-{number}'`

Set the default PDF filename format.
:::


::: reference
### `checkinTemplate`

**Type:** `string` · **Default:** `''`

Set a template to be shown when checking into an event. See `events/templates/check-in.html` for an example.
:::


::: reference
### `ticketsShippable`

**Type:** `bool` · **Default:** `false`

Whether a ticket should be classified as shippable. If `false` (the default) no shipping methods will be able to be selected, if tickets are the only items in the cart.
:::


::: reference
### `applyPendingTicketUpdates`

**Type:** `bool` · **Default:** `false`

Whether to automatically queue ticket updates when saving an event that has pending session or ticket type changes. Defaults to `false`, which preserves the manual **Apply ticket updates** workflow.
:::


::: reference
### `releaseCapacityOrderStatusHandles`

**Type:** `array` · **Default:** `['cancelled', 'canceled', 'refunded']`

Commerce order status handles that should cancel purchased tickets for an order, restoring event capacity. Defaults to `cancelled`, `canceled`, and `refunded`. Set to an empty array to disable automatic cancellation.
:::


::: reference
### `purchasedTicketTrashRetentionDays`

**Type:** `int` · **Default:** `30`

Number of days to retain soft-deleted purchased tickets before Craft’s garbage collection permanently deletes them. Defaults to `30`. Set to `0` to disable automatic purging.
:::


::: reference
### `cancelPurchasedTicketsOnRefund`

**Type:** `bool` · **Default:** `true`

Whether Commerce refunds should automatically cancel purchased tickets. Defaults to `true`.
:::


::: reference
### `allowRestoreWhenOrderCancelled`

**Type:** `bool` · **Default:** `false`

Whether cancelled purchased tickets can be restored when their order is in a release-capacity status. Defaults to `false`.
:::


::: reference
### `pdfAllowRemoteImages`

**Type:** `bool` · **Default:** `false`

Whether to allow remote images in the PDF.
:::


::: reference
### `pdfPaperSize`

**Type:** `string` · **Default:** `'letter'`

Sets the paper size for the PDF.
:::


::: reference
### `pdfPaperOrientation`

**Type:** `string` · **Default:** `'portrait'`

Sets the paper orientation for the PDF.
:::


## Control Panel
You can also manage configuration settings through the Control Panel by visiting Settings → Events.
