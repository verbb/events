# Events
Events can be used to extend the functionality of Events.


## Register a Listener

Register listeners from a custom module or plugin that is bootstrapped for the requests where the event occurs. Put the `use` imports at the top of its PHP file and the `Event::on(...)` call inside its `init()` method, after `parent::init()`. Do not place the listener in a Twig template or modify this plugin's source to register it.

Choose a hook whose timing matches your task. Cancellation depends on the particular event and emitter, as described for each hook below. Test a listener on the operation it affects, including any relevant queue or console path.

## Ticket PDF Related Events

### The `beforeRenderPdf` Event
Event handlers can override Ticket’s PDF generation by setting the `pdf` property on the event to a custom-rendered PDF.
The event that is triggered before the PDF or a ticket is being rendered.

```php
use verbb\events\events\PdfEvent;
use verbb\events\services\Pdf;
use yii\base\Event;

Event::on(Pdf::class, Pdf::EVENT_BEFORE_RENDER_PDF, function(PdfEvent $event) {
     // Roll out our own custom PDF
});
```

### The `afterRenderPdf` Event
The event that is triggered after the PDF or a ticket has been rendered.

```php
use verbb\events\events\PdfEvent;
use verbb\events\services\Pdf;
use yii\base\Event;

Event::on(Pdf::class, Pdf::EVENT_AFTER_RENDER_PDF, function(PdfEvent $event) {
     // Add a watermark to the PDF or forward it to the accounting dpt.
});
```

### The `modifyRenderOptions` Event
Plugins can get modify the DomPDF render options

```php
use verbb\events\events\PdfRenderOptionsEvent;
use verbb\events\services\Pdf;
use yii\base\Event;

Event::on(Pdf::class, Pdf::EVENT_MODIFY_RENDER_OPTIONS, function(PdfRenderOptionsEvent $event) {

});
```


## Event Related Events

### The `beforeSaveEvent` Event

The event that is triggered before an event is saved. Event handlers can prevent the event from getting saved by setting `$event->isValid` to false.

```php
use craft\events\ModelEvent;
use verbb\events\elements\Event as EventElement;
use yii\base\Event;

Event::on(EventElement::class, EventElement::EVENT_BEFORE_SAVE, function(ModelEvent $event) {
    $isNew = $event->isNew;
    $eventElement = $event->sender;
    $event->isValid = false;
});
```

### The `afterSaveEvent` Event

The event that is triggered after an event has been saved

```php
use craft\events\ModelEvent;
use verbb\events\elements\Event as EventElement;
use yii\base\Event;

Event::on(EventElement::class, EventElement::EVENT_AFTER_SAVE, function(ModelEvent $event) {
    $isNew = $event->isNew;
    $eventElement = $event->sender;
});
```

### The `beforeDeleteEvent` Event
The event that is triggered before an event is deleted.

The `isValid` event property can be set to `false` to prevent the deletion from proceeding.

```php
use verbb\events\elements\Event as EventElement;
use yii\base\Event;

Event::on(EventElement::class, EventElement::EVENT_BEFORE_DELETE, function(Event $event) {
    $eventElement = $event->sender;
    $event->isValid = false;
});
```

### The `afterDeleteEvent` Event
The event that is triggered after an event is deleted.

```php
use verbb\events\elements\Event as EventElement;
use yii\base\Event;

Event::on(EventElement::class, EventElement::EVENT_AFTER_DELETE, function(Event $event) {
    $eventElement = $event->sender;
});
```


## Event Type Related Events

### The `beforeSaveEventType` Event

The event that is triggered before an event type is being saved.

```php
use verbb\events\events\EventTypeEvent;
use verbb\events\services\EventTypes;
use yii\base\Event;

Event::on(EventTypes::class, EventTypes::EVENT_BEFORE_SAVE_EVENTTYPE, function(EventTypeEvent $event) {
     // Maybe create an audit trail of this action.
});
```

### The `afterSaveEventType` Event

The event that is triggered after an event type has been saved.

```php
use verbb\events\events\EventTypeEvent;
use verbb\events\services\EventTypes;
use yii\base\Event;

Event::on(EventTypes::class, EventTypes::EVENT_AFTER_SAVE_EVENTTYPE, function(EventTypeEvent $event) {
     // Maybe prepare some third party system for a new event type
});
```



## Ticket Related Events

### The `beforeSaveTicket` Event

The event that is triggered before a ticket is saved. Event handlers can prevent the ticket from getting saved by setting `$event->isValid` to false.

```php
use craft\events\ModelEvent;
use verbb\events\elements\Ticket;
use yii\base\Event;

Event::on(Ticket::class, Ticket::EVENT_BEFORE_SAVE, function(ModelEvent $event) {
    $isNew = $event->isNew;
    $ticket = $event->sender;
    $event->isValid = false;
});
```

### The `afterSaveTicket` Event

The event that is triggered after a ticket has been saved

```php
use craft\events\ModelEvent;
use verbb\events\elements\Ticket;
use yii\base\Event;

Event::on(Ticket::class, Ticket::EVENT_AFTER_SAVE, function(ModelEvent $event) {
    $isNew = $event->isNew;
    $ticket = $event->sender;
});
```

### The `beforeDeleteTicket` Event
The event that is triggered before a ticket is deleted.

The `isValid` event property can be set to `false` to prevent the deletion from proceeding.

```php
use verbb\events\elements\Ticket;
use yii\base\Event;

Event::on(Ticket::class, Ticket::EVENT_BEFORE_DELETE, function(Event $event) {
    $ticket = $event->sender;
    $event->isValid = false;
});
```

### The `afterDeleteTicket` Event
The event that is triggered after a ticket is deleted.

```php
use verbb\events\elements\Ticket;
use yii\base\Event;

Event::on(Ticket::class, Ticket::EVENT_AFTER_DELETE, function(Event $event) {
    $ticket = $event->sender;
});
```

### The `beforeCaptureTicketSnapshot` Event

The event that is triggered before we capture a ticket’s field data, and customize which fields are included.

```php
use verbb\events\elements\Ticket;
use verbb\events\events\CustomizeTicketSnapshotFieldsEvent;

Event::on(Ticket::class, Variant::EVENT_BEFORE_CAPTURE_TICKET_SNAPSHOT, function(CustomizeTicketSnapshotFieldsEvent $event) {
    $ticket = $event->ticket;
    $fields = $event->fields;
    // Modify fields, or set to `null` to capture all.
});
```

### The `afterCaptureTicketSnapshot` Event

The event that is triggered after we capture a ticket’s field data, and customize, extend, or redact the data to be persisted.

```php
use verbb\events\elements\Ticket;
use verbb\events\events\CustomizeTicketSnapshotDataEvent;

Event::on(Ticket::class, Ticket::EVENT_AFTER_CAPTURE_TICKET_SNAPSHOT, function(CustomizeTicketSnapshotFieldsEvent $event) {
    $ticket = $event->ticket;
    $data = $event->fieldData;
    // Modify or redact captured `$data`...
});
```

### The `beforeCaptureEventSnapshot` Event

The event that is triggered before we capture an event’s field data, and customize which fields are included.

```php
use verbb\events\elements\Event as EventElement;
use verbb\events\events\CustomizeEventSnapshotFieldsEvent;

Event::on(EventElement::class, EventElement::EVENT_BEFORE_CAPTURE_EVENT_SNAPSHOT, function(CustomizeEventSnapshotFieldsEvent $event) {
    $eventElement = $event->event;
    $fields = $event->fields;
    // Modify fields, or set to `null` to capture all.
});
```

### The `afterCaptureEventSnapshot` Event

The event that is triggered after we capture an event’s field data, and customize, extend, or redact the data to be persisted.

```php
use verbb\events\elements\Event as EventElement;
use verbb\events\events\CustomizeEventSnapshotDataEvent;

Event::on(EventElement::class, EventElement::EVENT_AFTER_CAPTURE_EVENT_SNAPSHOT, function(CustomizeProductSnapshotFieldsEvent $event) {
    $eventElement = $event->event;
    $data = $event->fieldData;
    // Modify or redact captured `$data`...
});
```


## Purchased Ticket Related Events

### The `beforeSavePurchasedTicket` Event

The event that is triggered before a purchased ticket is saved. Event handlers can prevent the purchased ticket from getting saved by setting `$event->isValid` to false.

```php
use craft\events\ModelEvent;
use verbb\events\elements\PurchasedTicket;
use yii\base\Event;

Event::on(PurchasedTicket::class, PurchasedTicket::EVENT_BEFORE_SAVE, function(ModelEvent $event) {
    $isNew = $event->isNew;
    $purchasedTicket = $event->sender;
    $event->isValid = false;
});
```

### The `afterSavePurchasedTicket` Event

The event that is triggered after a purchased ticket has been saved

```php
use craft\events\ModelEvent;
use verbb\events\elements\PurchasedTicket;
use yii\base\Event;

Event::on(PurchasedTicket::class, PurchasedTicket::EVENT_AFTER_SAVE, function(ModelEvent $event) {
    $isNew = $event->isNew;
    $purchasedTicket = $event->sender;
});
```

### The `beforeDeletePurchasedTicket` Event
The event that is triggered before a purchased ticket is deleted.

The `isValid` event property can be set to `false` to prevent the deletion from proceeding.

```php
use verbb\events\elements\PurchasedTicket;
use yii\base\Event;

Event::on(PurchasedTicket::class, PurchasedTicket::EVENT_BEFORE_DELETE, function(Event $event) {
    $purchasedTicket = $event->sender;
    $event->isValid = false;
});
```

### The `afterDeletePurchasedTicket` Event
The event that is triggered after a purchased ticket is deleted.

```php
use verbb\events\elements\PurchasedTicket;
use yii\base\Event;

Event::on(PurchasedTicket::class, PurchasedTicket::EVENT_AFTER_DELETE, function(Event $event) {
    $purchasedTicket = $event->sender;
});
```

### The `beforeCheckIn` Event
The event that is triggered before a purchased ticket is checked-in.

The `isValid` event property can be set to `false` to prevent the check-in from proceeding.

```php
use verbb\events\events\PurchasedTicketEvent;
use verbb\events\services\PurchasedTickets;
use yii\base\Event;

Event::on(PurchasedTickets::class, PurchasedTickets::EVENT_BEFORE_CHECK_IN, function(PurchasedTicketEvent $event) {
    $purchasedTicket = $event->purchasedTicket;
    $event->isValid = false;
});
```

### The `afterCheckIn` Event
The event that is triggered after a purchased ticket is checked-in.

```php
use verbb\events\events\PurchasedTicketEvent;
use verbb\events\services\PurchasedTickets;
use yii\base\Event;

Event::on(PurchasedTickets::class, PurchasedTickets::EVENT_AFTER_CHECK_IN, function(PurchasedTicketEvent $event) {
    $purchasedTicket = $event->purchasedTicket;
});
```

### The `beforeCheckOut` Event
The event that is triggered before a purchased ticket is checked-out.

The `isValid` event property can be set to `false` to prevent the check-out from proceeding.

```php
use verbb\events\events\PurchasedTicketEvent;
use verbb\events\services\PurchasedTickets;
use yii\base\Event;

Event::on(PurchasedTickets::class, PurchasedTickets::EVENT_BEFORE_CHECK_OUT, function(PurchasedTicketEvent $event) {
    $purchasedTicket = $event->purchasedTicket;
    $event->isValid = false;
});
```

### The `afterCheckOut` Event
The event that is triggered after a purchased ticket is checked-out.

```php
use verbb\events\events\PurchasedTicketEvent;
use verbb\events\services\PurchasedTickets;
use yii\base\Event;

Event::on(PurchasedTickets::class, PurchasedTickets::EVENT_AFTER_CHECK_OUT, function(PurchasedTicketEvent $event) {
    $purchasedTicket = $event->purchasedTicket;
});
```

### The `beforeCancel` Event
The event that is triggered before a purchased ticket reservation is cancelled.

The `isValid` event property can be set to `false` to prevent the cancellation from proceeding.

```php
use verbb\events\events\PurchasedTicketEvent;
use verbb\events\services\PurchasedTickets;
use yii\base\Event;

Event::on(PurchasedTickets::class, PurchasedTickets::EVENT_BEFORE_CANCEL, function(PurchasedTicketEvent $event) {
    $purchasedTicket = $event->purchasedTicket;
    $reason = $event->reason;
    $event->isValid = false;
});
```

### The `afterCancel` Event
The event that is triggered after a purchased ticket reservation is cancelled.

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
The event that is triggered before a cancelled purchased ticket reservation is restored.

The `isValid` event property can be set to `false` to prevent the restore from proceeding.

```php
use verbb\events\events\PurchasedTicketEvent;
use verbb\events\services\PurchasedTickets;
use yii\base\Event;

Event::on(PurchasedTickets::class, PurchasedTickets::EVENT_BEFORE_RESTORE, function(PurchasedTicketEvent $event) {
    $purchasedTicket = $event->purchasedTicket;
    $event->isValid = false;
});
```

### The `afterRestore` Event
The event that is triggered after a cancelled purchased ticket reservation is restored.

```php
use verbb\events\events\PurchasedTicketEvent;
use verbb\events\services\PurchasedTickets;
use yii\base\Event;

Event::on(PurchasedTickets::class, PurchasedTickets::EVENT_AFTER_RESTORE, function(PurchasedTicketEvent $event) {
    $purchasedTicket = $event->purchasedTicket;
});
```

