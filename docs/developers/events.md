# Events

Events provides events for extending its functionality. Modules and plugins can register event listeners, typically in their `init()` methods, to change Events’ behaviour.

The PHP events on this page are separate from the [Event elements](docs:feature-tour/events) you sell tickets for. Where an example needs both, it imports the element as `EventElement` so it doesn’t clash with `yii\base\Event`.

## Ticket PDF Events

### The `beforeRenderPdf` Event
The event that is triggered before a ticket PDF is rendered.

Listeners can change `$event->template`, `$event->variables`, `$event->order` and `$event->option` before Events renders the template. To replace the PDF entirely, set `$event->pdf` to your own rendered PDF string. Events then returns it as-is, skipping its template and Dompdf, and the `afterRenderPdf` event doesn’t fire.

```php
use verbb\events\events\PdfEvent;
use verbb\events\services\Pdf;
use yii\base\Event;

Event::on(Pdf::class, Pdf::EVENT_BEFORE_RENDER_PDF, function(PdfEvent $event) {
    // Use a different template for orders paid in full
    if ($event->order?->getIsPaid()) {
        $event->template = '_pdf/paid-tickets';
    }
});
```

### The `afterRenderPdf` Event
The event that is triggered after a ticket PDF has been rendered.

`$event->pdf` holds the rendered PDF string. Listeners can replace it, and Events returns whatever it holds when the listener finishes. This event doesn’t fire when the PDF is requested as plain HTML with `format=plain`.

```php
use verbb\events\events\PdfEvent;
use verbb\events\services\Pdf;
use yii\base\Event;

Event::on(Pdf::class, Pdf::EVENT_AFTER_RENDER_PDF, function(PdfEvent $event) {
    $pdf = $event->pdf;
    $order = $event->order;

    // Store or forward a copy of the PDF
});
```

### The `modifyRenderOptions` Event
The event that is triggered before Events passes its render options to Dompdf.

`$event->options` is a `Dompdf\Options` object, already set up with Events’ temp and font folders and the [pdfAllowRemoteImages](docs:get-started/configuration#pdfallowremoteimages) setting. Change it in place to adjust how Dompdf renders the PDF.

```php
use verbb\events\events\PdfRenderOptionsEvent;
use verbb\events\services\Pdf;
use yii\base\Event;

Event::on(Pdf::class, Pdf::EVENT_MODIFY_RENDER_OPTIONS, function(PdfRenderOptionsEvent $event) {
    $event->options->setDefaultFont('DejaVu Sans');
    $event->options->setDpi(150);
});
```

## Event Element Events

### The `beforeSave` Event
The event that is triggered before an Event element is saved.

This is Craft’s element event. Set `$event->isValid` to `false` to prevent the event from being saved.

```php
use craft\events\ModelEvent;
use verbb\events\elements\Event as EventElement;
use yii\base\Event;

Event::on(EventElement::class, EventElement::EVENT_BEFORE_SAVE, function(ModelEvent $event) {
    $eventElement = $event->sender;
    $isNew = $event->isNew;

    // Prevent the save
    $event->isValid = false;
});
```

### The `afterSave` Event
The event that is triggered after an Event element has been saved.

```php
use craft\events\ModelEvent;
use verbb\events\elements\Event as EventElement;
use yii\base\Event;

Event::on(EventElement::class, EventElement::EVENT_AFTER_SAVE, function(ModelEvent $event) {
    $eventElement = $event->sender;
    $isNew = $event->isNew;
});
```

### The `beforeDelete` Event
The event that is triggered before an Event element is deleted.

Set `$event->isValid` to `false` to prevent the deletion.

```php
use verbb\events\elements\Event as EventElement;
use yii\base\Event;
use yii\base\ModelEvent;

Event::on(EventElement::class, EventElement::EVENT_BEFORE_DELETE, function(ModelEvent $event) {
    $eventElement = $event->sender;

    // Prevent the deletion
    $event->isValid = false;
});
```

### The `afterDelete` Event
The event that is triggered after an Event element is deleted.

```php
use verbb\events\elements\Event as EventElement;
use yii\base\Event;

Event::on(EventElement::class, EventElement::EVENT_AFTER_DELETE, function(Event $event) {
    $eventElement = $event->sender;
});
```

## Event Type Events

### The `beforeSaveEventType` Event
The event that is triggered before an event type is saved.

Listeners can change `$event->eventType` before it’s validated and saved. `$event->isNew` tells you whether it’s a new event type. The event can’t cancel the save.

```php
use verbb\events\events\EventTypeEvent;
use verbb\events\services\EventTypes;
use yii\base\Event;

Event::on(EventTypes::class, EventTypes::EVENT_BEFORE_SAVE_EVENTTYPE, function(EventTypeEvent $event) {
    $eventType = $event->eventType;
    $isNew = $event->isNew;
});
```

### The `afterSaveEventType` Event
The event that is triggered after an event type has been saved.

```php
use verbb\events\events\EventTypeEvent;
use verbb\events\services\EventTypes;
use yii\base\Event;

Event::on(EventTypes::class, EventTypes::EVENT_AFTER_SAVE_EVENTTYPE, function(EventTypeEvent $event) {
    $eventType = $event->eventType;
    $isNew = $event->isNew;
});
```

## Session Events

### The `registerFrequencyTypes` Event
The event that is triggered when registering the frequencies a session can repeat on, so a module or plugin can add its own. See [Custom Frequency Types](docs:developers/custom-frequency-types) for a complete example.

```php
use craft\events\RegisterComponentTypesEvent;
use modules\sitemodule\frequencies\Weekdays;
use verbb\events\services\Sessions;
use yii\base\Event;

Event::on(Sessions::class, Sessions::EVENT_REGISTER_FREQUENCY_TYPES, function(RegisterComponentTypesEvent $event) {
    $event->types[] = Weekdays::class;
});
```

## Ticket Events

### The `beforeSave` Event
The event that is triggered before a ticket is saved.

This is Craft’s element event. Set `$event->isValid` to `false` to prevent the ticket from being saved.

```php
use craft\events\ModelEvent;
use verbb\events\elements\Ticket;
use yii\base\Event;

Event::on(Ticket::class, Ticket::EVENT_BEFORE_SAVE, function(ModelEvent $event) {
    $ticket = $event->sender;
    $isNew = $event->isNew;

    // Prevent the save
    $event->isValid = false;
});
```

### The `afterSave` Event
The event that is triggered after a ticket has been saved.

```php
use craft\events\ModelEvent;
use verbb\events\elements\Ticket;
use yii\base\Event;

Event::on(Ticket::class, Ticket::EVENT_AFTER_SAVE, function(ModelEvent $event) {
    $ticket = $event->sender;
    $isNew = $event->isNew;
});
```

### The `beforeDelete` Event
The event that is triggered before a ticket is deleted.

Set `$event->isValid` to `false` to prevent the deletion.

```php
use verbb\events\elements\Ticket;
use yii\base\Event;
use yii\base\ModelEvent;

Event::on(Ticket::class, Ticket::EVENT_BEFORE_DELETE, function(ModelEvent $event) {
    $ticket = $event->sender;

    // Prevent the deletion
    $event->isValid = false;
});
```

### The `afterDelete` Event
The event that is triggered after a ticket is deleted.

```php
use verbb\events\elements\Ticket;
use yii\base\Event;

Event::on(Ticket::class, Ticket::EVENT_AFTER_DELETE, function(Event $event) {
    $ticket = $event->sender;
});
```

## Ticket Snapshot Events

When a ticket is added to a Commerce order, Commerce stores a snapshot of it on the line item. Events includes the ticket’s attributes and its event’s attributes in that snapshot, without custom fields by default. All four snapshot events are triggered on the `Ticket` class.

### The `beforeCaptureTicketSnapshot` Event
The event that is triggered before a ticket’s data is captured for a line item snapshot.

Add custom field handles to `$event->fields` to include those fields in the snapshot.

```php
use verbb\events\elements\Ticket;
use verbb\events\events\CustomizeTicketSnapshotFieldsEvent;
use yii\base\Event;

Event::on(Ticket::class, Ticket::EVENT_BEFORE_CAPTURE_TICKET_SNAPSHOT, function(CustomizeTicketSnapshotFieldsEvent $event) {
    $ticket = $event->ticket;
    $event->fields[] = 'ticketNotes';
});
```

### The `afterCaptureTicketSnapshot` Event
The event that is triggered after a ticket’s data is captured for a line item snapshot.

`$event->fieldData` holds the captured ticket data. Listeners can add to it, change it or remove values before it’s stored.

```php
use verbb\events\elements\Ticket;
use verbb\events\events\CustomizeTicketSnapshotDataEvent;
use yii\base\Event;

Event::on(Ticket::class, Ticket::EVENT_AFTER_CAPTURE_TICKET_SNAPSHOT, function(CustomizeTicketSnapshotDataEvent $event) {
    $ticket = $event->ticket;

    // Don't store the ticket's SKU
    unset($event->fieldData['sku']);
});
```

### The `beforeCaptureEventSnapshot` Event
The event that is triggered before a ticket’s event data is captured for a line item snapshot.

Add custom field handles to `$event->fields` to include those fields of the Event element in the snapshot. `$event->event` is the ticket’s Event element.

```php
use verbb\events\elements\Ticket;
use verbb\events\events\CustomizeEventSnapshotFieldsEvent;
use yii\base\Event;

Event::on(Ticket::class, Ticket::EVENT_BEFORE_CAPTURE_EVENT_SNAPSHOT, function(CustomizeEventSnapshotFieldsEvent $event) {
    $eventElement = $event->event;
    $event->fields[] = 'venue';
});
```

### The `afterCaptureEventSnapshot` Event
The event that is triggered after a ticket’s event data is captured for a line item snapshot.

`$event->fieldData` holds the captured event data. Listeners can add to it, change it or remove values before it’s stored.

```php
use verbb\events\elements\Ticket;
use verbb\events\events\CustomizeEventSnapshotDataEvent;
use yii\base\Event;

Event::on(Ticket::class, Ticket::EVENT_AFTER_CAPTURE_EVENT_SNAPSHOT, function(CustomizeEventSnapshotDataEvent $event) {
    $eventElement = $event->event;
    $event->fieldData['organiser'] = 'Happy Lager Events';
});
```

## Purchased Ticket Events

### The `beforeSave` Event
The event that is triggered before a purchased ticket is saved.

This is Craft’s element event. Set `$event->isValid` to `false` to prevent the purchased ticket from being saved.

```php
use craft\events\ModelEvent;
use verbb\events\elements\PurchasedTicket;
use yii\base\Event;

Event::on(PurchasedTicket::class, PurchasedTicket::EVENT_BEFORE_SAVE, function(ModelEvent $event) {
    $purchasedTicket = $event->sender;
    $isNew = $event->isNew;

    // Prevent the save
    $event->isValid = false;
});
```

### The `afterSave` Event
The event that is triggered after a purchased ticket has been saved.

```php
use craft\events\ModelEvent;
use verbb\events\elements\PurchasedTicket;
use yii\base\Event;

Event::on(PurchasedTicket::class, PurchasedTicket::EVENT_AFTER_SAVE, function(ModelEvent $event) {
    $purchasedTicket = $event->sender;
    $isNew = $event->isNew;
});
```

### The `beforeDelete` Event
The event that is triggered before a purchased ticket is deleted.

Set `$event->isValid` to `false` to prevent the deletion.

```php
use verbb\events\elements\PurchasedTicket;
use yii\base\Event;
use yii\base\ModelEvent;

Event::on(PurchasedTicket::class, PurchasedTicket::EVENT_BEFORE_DELETE, function(ModelEvent $event) {
    $purchasedTicket = $event->sender;

    // Prevent the deletion
    $event->isValid = false;
});
```

### The `afterDelete` Event
The event that is triggered after a purchased ticket is deleted.

```php
use verbb\events\elements\PurchasedTicket;
use yii\base\Event;

Event::on(PurchasedTicket::class, PurchasedTicket::EVENT_AFTER_DELETE, function(Event $event) {
    $purchasedTicket = $event->sender;
});
```

## Check-In Events

### The `beforeCheckIn` Event
The event that is triggered before a purchased ticket is checked in.

`$event->purchasedTicket` already has `checkedIn` set to `true` and `checkedInDate` set to the current time. Listeners can change `checkedInDate` before it’s saved. Set `$event->isValid` to `false`, or set `checkedIn` back to `false`, to prevent the check-in. The ticket holder then isn’t checked in, and the check-in request reports a failure.

```php
use verbb\events\events\PurchasedTicketEvent;
use verbb\events\services\PurchasedTickets;
use yii\base\Event;

Event::on(PurchasedTickets::class, PurchasedTickets::EVENT_BEFORE_CHECK_IN, function(PurchasedTicketEvent $event) {
    $purchasedTicket = $event->purchasedTicket;

    // Only allow check-in on the day of the session
    $session = $purchasedTicket->getSession();

    if ($session && $session->startDate->format('Y-m-d') !== (new \DateTime())->format('Y-m-d')) {
        $event->isValid = false;
    }
});
```

### The `afterCheckIn` Event
The event that is triggered after a purchased ticket has been checked in.

```php
use verbb\events\events\PurchasedTicketEvent;
use verbb\events\services\PurchasedTickets;
use yii\base\Event;

Event::on(PurchasedTickets::class, PurchasedTickets::EVENT_AFTER_CHECK_IN, function(PurchasedTicketEvent $event) {
    $purchasedTicket = $event->purchasedTicket;
});
```

### The `beforeCheckOut` Event
The event that is triggered before a purchased ticket is checked out, which clears its check-in.

`$event->purchasedTicket` already has `checkedIn` set to `false` and `checkedInDate` cleared. Set `$event->isValid` to `false` to prevent the check-out from being saved. Cancelling a checked-in ticket also checks it out first, and the cancellation continues even if a listener prevents the check-out.

```php
use verbb\events\events\PurchasedTicketEvent;
use verbb\events\services\PurchasedTickets;
use yii\base\Event;

Event::on(PurchasedTickets::class, PurchasedTickets::EVENT_BEFORE_CHECK_OUT, function(PurchasedTicketEvent $event) {
    $purchasedTicket = $event->purchasedTicket;

    // Prevent the check-out
    $event->isValid = false;
});
```

### The `afterCheckOut` Event
The event that is triggered after a purchased ticket has been checked out.

```php
use verbb\events\events\PurchasedTicketEvent;
use verbb\events\services\PurchasedTickets;
use yii\base\Event;

Event::on(PurchasedTickets::class, PurchasedTickets::EVENT_AFTER_CHECK_OUT, function(PurchasedTicketEvent $event) {
    $purchasedTicket = $event->purchasedTicket;
});
```

## Reservation Events

### The `beforeCancel` Event
The event that is triggered before a purchased ticket’s reservation is cancelled.

`$event->reason` holds the cancellation reason, if one was given. Set `$event->isValid` to `false` to prevent the cancellation, which leaves the reservation active.

```php
use verbb\events\events\PurchasedTicketEvent;
use verbb\events\services\PurchasedTickets;
use yii\base\Event;

Event::on(PurchasedTickets::class, PurchasedTickets::EVENT_BEFORE_CANCEL, function(PurchasedTicketEvent $event) {
    $purchasedTicket = $event->purchasedTicket;
    $reason = $event->reason;

    // Don't allow cancelling tickets that have been checked in
    if ($purchasedTicket->checkedIn) {
        $event->isValid = false;
    }
});
```

### The `afterCancel` Event
The event that is triggered after a purchased ticket’s reservation has been cancelled.

```php
use verbb\events\events\PurchasedTicketEvent;
use verbb\events\services\PurchasedTickets;
use yii\base\Event;

Event::on(PurchasedTickets::class, PurchasedTickets::EVENT_AFTER_CANCEL, function(PurchasedTicketEvent $event) {
    $purchasedTicket = $event->purchasedTicket;
    $reason = $event->reason;
});
```

### The `beforeRestore` Event
The event that is triggered before a cancelled purchased ticket’s reservation is restored.

Set `$event->isValid` to `false` to prevent the restore, which leaves the reservation cancelled. Events still checks there’s enough capacity after the event, and won’t restore a reservation that no longer fits.

```php
use verbb\events\events\PurchasedTicketEvent;
use verbb\events\services\PurchasedTickets;
use yii\base\Event;

Event::on(PurchasedTickets::class, PurchasedTickets::EVENT_BEFORE_RESTORE, function(PurchasedTicketEvent $event) {
    $purchasedTicket = $event->purchasedTicket;

    // Prevent the restore
    $event->isValid = false;
});
```

### The `afterRestore` Event
The event that is triggered after a cancelled purchased ticket’s reservation has been restored.

```php
use verbb\events\events\PurchasedTicketEvent;
use verbb\events\services\PurchasedTickets;
use yii\base\Event;

Event::on(PurchasedTickets::class, PurchasedTickets::EVENT_AFTER_RESTORE, function(PurchasedTicketEvent $event) {
    $purchasedTicket = $event->purchasedTicket;
});
```
