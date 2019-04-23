# Events

Events can be used to extend the functionality of Events.

## Ticket PDF related events

### The `beforeRenderPdf` event

Event handlers can override Ticket’s PDF generation by setting the `pdf` property on the event to a custom-rendered PDF.
Plugins can get notified before the PDF or a ticket is being rendered.

```php
use craft\commerce\events\PdfEvent;
use verbb\events\services\Pdf;
use yii\base\Event;

Event::on(Pdf::class, Pdf::EVENT_BEFORE_RENDER_PDF, function(PdfEvent $e) {
     // Roll out our own custom PDF
});
```

### The `afterRenderPdf` event

Plugins can get notified after the PDF or a ticket has been rendered.

```php
use craft\commerce\events\PdfEvent;
use verbb\events\services\PdfService as Pdf;
use yii\base\Event;

Event::on(Pdf::class, Pdf::EVENT_AFTER_RENDER_PDF, function(PdfEvent $e) {
     // Add a watermark to the PDF or forward it to the accounting dpt.
});
```


## Event related events

### The `beforeSaveEvent` event

Plugins can get notified before an event is saved. Event handlers can prevent the event from getting sent by setting `$event->isValid` to false.

```php
use verbb\events\elements\Event;
use yii\base\Event;

Event::on(Event::class, Event::EVENT_BEFORE_SAVE, function(Event $e) {
    $code = $event->sender;
    $event->isValid = false;
});
```

### The `afterSaveEvent` event

Plugins can get notified after an event has been saved

```php
use verbb\events\elements\Event;
use yii\base\Event;

Event::on(Event::class, Event::EVENT_AFTER_SAVE, function(Event $e) {
    $code = $event->sender;
});
```


## Event Type related events

### The `beforeSaveEventType` event

Plugins can get notified before an event type is being saved.

```php
use verbb\events\events\EventTypeEvent;
use verbb\events\services\EventTypes;
use yii\base\Event;

Event::on(EventTypes::class, EventTypes::EVENT_BEFORE_SAVE_EVENTTYPE, function(EventTypeEvent $e) {
     // Maybe create an audit trail of this action.
});
```

### The `afterSaveEventType` event

Plugins can get notified after an event type has been saved.

```php
use verbb\events\events\EventTypeEvent;
use verbb\events\services\EventTypes;
use yii\base\Event;

Event::on(EventTypes::class, EventTypes::EVENT_AFTER_SAVE_EVENTTYPE, function(EventTypeEvent $e) {
     // Maybe prepare some third party system for a new event type
});
```



## Ticket related events

### The `beforeSaveTicket` event

Plugins can get notified before a ticket is saved. Event handlers can prevent the ticket from getting sent by setting `$event->isValid` to false.

```php
use verbb\events\elements\Ticket;
use yii\base\Event;

Event::on(Ticket::class, Ticket::EVENT_BEFORE_SAVE, function(Event $e) {
    $voucher = $event->sender;
    $event->isValid = false;
});
```

### The `afterSaveTicket` event

Plugins can get notified after a voucher has been saved

```php
use verbb\events\elements\Ticket;
use yii\base\Event;

Event::on(Ticket::class, Ticket::EVENT_AFTER_SAVE, function(Event $e) {
    $voucher = $event->sender;
});
```

### The `beforeCaptureTicketSnapshot` event

Plugins can get notified before we capture a ticket’s field data, and customize which fields are included.

```php
use verbb\events\elements\Ticket;
use verbb\events\events\CustomizeTicketSnapshotFieldsEvent;

Event::on(Ticket::class, Variant::EVENT_BEFORE_CAPTURE_TICKET_SNAPSHOT, function(CustomizeTicketSnapshotFieldsEvent $e) {
    $ticket = $e->ticket;
    $fields = $e->fields;
    // Modify fields, or set to `null` to capture all.
});
```

### The `afterCaptureTicketSnapshot` event

Plugins can get notified after we capture a ticket’s field data, and customize, extend, or redact the data to be persisted.

```php
use verbb\events\elements\Ticket;
use verbb\events\events\CustomizeTicketSnapshotDataEvent;

Event::on(Ticket::class, Ticket::EVENT_AFTER_CAPTURE_TICKET_SNAPSHOT, function(CustomizeTicketSnapshotFieldsEvent $e) {
    $ticket = $e->ticket;
    $data = $e->fieldData;
    // Modify or redact captured `$data`...
});
```

### The `beforeCaptureEventSnapshot` event

Plugins can get notified before we capture an event’s field data, and customize which fields are included.

```php
use verbb\events\elements\Event as EventElement;
use verbb\events\events\CustomizeEventSnapshotFieldsEvent;

Event::on(EventElement::class, EventElement::EVENT_BEFORE_CAPTURE_EVENT_SNAPSHOT, function(CustomizeEventSnapshotFieldsEvent $e) {
    $event = $e->event;
    $fields = $e->fields;
    // Modify fields, or set to `null` to capture all.
});
```

### The `afterCaptureEventSnapshot` event

Plugins can get notified after we capture an event’s field data, and customize, extend, or redact the data to be persisted.

```php
use verbb\events\elements\Event as EventElement;
use verbb\events\events\CustomizeEventSnapshotDataEvent;

Event::on(EventElement::class, EventElement::EVENT_AFTER_CAPTURE_EVENT_SNAPSHOT, function(CustomizeProductSnapshotFieldsEvent $e) {
    $event = $e->event;
    $data = $e->fieldData;
    // Modify or redact captured `$data`...
});
```
