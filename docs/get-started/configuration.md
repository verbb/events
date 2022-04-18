# Configuration
Create a `events.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these per environment.

The below shows the defaults already used by Events, so you don't need to add these options unless you want to modify the values.

```php
<?php

return [
    '*' => [
        'pluginName' => 'Events',
        'ticketSKULength' => 10,
        'ticketPdfPath' => 'shop/_pdf/tickets',
        'ticketPdfFilenameFormat' => 'Tickets-{number}',
        'checkinTemplate' => 'events/check-in',
        'ticketsShippable' => false,
        
        'pdfAllowRemoteImages' => false,
        'pdfPaperSize' => 'letter',
        'pdfPaperOrientation' => 'portrait',
    ]
];
```

## Configuration options
- `pluginName` - Change the plugin name.
- `ticketSKULength` - Purchased tickets have a unique, auto-generated SKU. Use this value to set the desired length.
- `ticketPdfPath` - Set the path to your PDF.
- `ticketPdfFilenameFormat` - Set the default PDF filename format.
- `checkinTemplate` - Set a template to be shown when checking into an event. See `events/templates/check-in.html` for an example.
- `ticketsShippable` - Whether a ticket should be classified as shippable. If `false` (the default) no shipping methods will be able to be selected, if tickets are the only items in the cart.

- `pdfAllowRemoteImages` - Whether to allow remote images in the PDF.
- `pdfPaperSize` - Sets the paper size for the PDF.
- `pdfPaperOrientation` - Sets the paper orientation for the PDF.

## Control Panel
You can also manage configuration settings through the Control Panel by visiting Settings → Events.
