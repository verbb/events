// craft-screenshots: current-event-and-ticket
/** Seed a realistic event and an inert sample ticket for Events screenshots. */

use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\db\Query;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\elements\Session;
use verbb\events\elements\TicketType;
use verbb\events\fieldlayoutelements\EventTitleField;
use verbb\events\fieldlayoutelements\SessionAllDayField;
use verbb\events\fieldlayoutelements\SessionEndDateTimeField;
use verbb\events\fieldlayoutelements\SessionFrequencyField;
use verbb\events\fieldlayoutelements\SessionStartDateTimeField;
use verbb\events\fieldlayoutelements\SessionsField;
use verbb\events\fieldlayoutelements\TicketTypeAllowedQtyField;
use verbb\events\fieldlayoutelements\TicketTypeCapacityField;
use verbb\events\fieldlayoutelements\TicketTypePriceField;
use verbb\events\fieldlayoutelements\TicketTypePromotableField;
use verbb\events\fieldlayoutelements\TicketTypeSeatsPerTicketField;
use verbb\events\fieldlayoutelements\TicketTypeTitleField;
use verbb\events\fieldlayoutelements\TicketTypesField;
use verbb\events\fieldlayoutelements\TicketsField;
use verbb\events\models\EventType;
use verbb\events\models\EventTypeSite;

$elements = Craft::$app->getElements();
$site = Craft::$app->getSites()->getPrimarySite();
$eventTypes = Events::$plugin->getEventTypes();
$typeHandle = 'festival';

$makeLayout = static function(string $type, array $elementClasses): FieldLayout {
    $layout = new FieldLayout(['type' => $type]);
    $tab = new FieldLayoutTab(['name' => Craft::t('app', 'Content'), 'layout' => $layout]);
    $tab->setElements(array_map(static fn(string $class) => Craft::createObject(['class' => $class]), $elementClasses));
    $layout->setTabs([$tab]);

    return $layout;
};

$eventType = $eventTypes->getEventTypeByHandle($typeHandle);

if (!$eventType) {
    $eventType = new EventType([
        'name' => 'Festival',
        'handle' => $typeHandle,
        'sessionTitleFormat' => '{dateSummary}',
        'ticketTitleFormat' => '{type.title} — {session.title}',
        'ticketSkuFormat' => 'EVENT-{event.id}-{session.id}-{type.id}',
    ]);

    $eventLayout = $makeLayout(Event::class, [
        EventTitleField::class,
        SessionsField::class,
        TicketTypesField::class,
        TicketsField::class,
    ]);
    $eventType->getBehavior('eventFieldLayout')->setFieldLayout($eventLayout);

    $sessionLayout = $makeLayout(Session::class, [
        SessionStartDateTimeField::class,
        SessionEndDateTimeField::class,
        SessionAllDayField::class,
        SessionFrequencyField::class,
    ]);
    $eventType->getBehavior('sessionFieldLayout')->setFieldLayout($sessionLayout);

    $ticketTypeLayout = $makeLayout(TicketType::class, [
        TicketTypeTitleField::class,
        TicketTypeCapacityField::class,
        TicketTypePriceField::class,
        TicketTypeAllowedQtyField::class,
        TicketTypeSeatsPerTicketField::class,
        TicketTypePromotableField::class,
    ]);
    $eventType->getBehavior('ticketFieldLayout')->setFieldLayout($ticketTypeLayout);

    $eventType->setSiteSettings([
        $site->id => new EventTypeSite([
            'siteId' => $site->id,
            'enabledByDefault' => true,
            'hasUrls' => false,
        ]),
    ]);

    if (!$eventTypes->saveEventType($eventType)) {
        throw new RuntimeException('Unable to save Events screenshot event type: ' . Json::encode($eventType->getErrors()));
    }

    $eventType = $eventTypes->getEventTypeByHandle($typeHandle);
}

$event = Event::find()
    ->typeId($eventType->id)
    ->title('Melbourne Design Weekend')
    ->siteId($site->id)
    ->status(null)
    ->one();

$countOwned = static function(string $table, int $eventId): int {
    return (int)(new Query())
        ->from(['nested' => $table])
        ->innerJoin(['owners' => '{{%elements_owners}}'], '[[owners.elementId]] = [[nested.id]]')
        ->where([
            'nested.primaryOwnerId' => $eventId,
            'owners.ownerId' => $eventId,
        ])
        ->count();
};

if (!$event) {
    $event = new Event([
        'typeId' => $eventType->id,
        'siteId' => $site->id,
        'title' => 'Melbourne Design Weekend',
        'slug' => 'melbourne-design-weekend',
        'capacity' => 420,
        'enabled' => true,
    ]);

    if (!$elements->saveElement($event)) {
        throw new RuntimeException('Unable to save Events screenshot event: ' . Json::encode($event->getErrors()));
    }
}

// Save nested elements against the persisted event. This mirrors Craft's
// nested-element manager without relying on a posted edit-form delta.
if ($countOwned('{{%events_sessions}}', $event->id) < 3) {
    $sessionData = [
        ['2026-10-17 10:00:00', '2026-10-17 17:00:00', 180],
        ['2026-10-18 10:00:00', '2026-10-18 17:00:00', 180],
        ['2026-10-19 09:30:00', '2026-10-19 14:00:00', 120],
    ];

    foreach ($sessionData as [$start, $end, $capacity]) {
        $session = new Session([
            // Match the screenshot user's control-panel timezone so the seeded
            // calendar dates read exactly as authored in the UI.
            'startDate' => new DateTime($start, new DateTimeZone('America/Los_Angeles')),
            'endDate' => new DateTime($end, new DateTimeZone('America/Los_Angeles')),
            'capacity' => $capacity,
            'allDay' => false,
            'enabled' => true,
        ]);
        $session->setPrimaryOwner($event);
        $session->setOwner($event);

        if (!$elements->saveElement($session)) {
            throw new RuntimeException('Unable to save Events screenshot session: ' . Json::encode($session->getErrors()));
        }
    }
}

if ($countOwned('{{%events_ticket_types}}', $event->id) < 2) {
    $ticketData = [
        ['Weekend pass', 220, 85, 1, 6],
        ['Day pass', 160, 45, 1, 8],
        ['Student pass', 40, 28, 1, 2],
    ];

    foreach ($ticketData as [$title, $capacity, $price, $minQty, $maxQty]) {
        $ticketType = new TicketType([
            'title' => $title,
            'capacity' => $capacity,
            'minQty' => $minQty,
            'maxQty' => $maxQty,
            'seatsPerTicket' => 1,
            'promotable' => true,
            'enabled' => true,
        ]);
        $ticketType->setPrice($price);
        $ticketType->setPrimaryOwner($event);
        $ticketType->setOwner($event);

        if (!$elements->saveElement($ticketType)) {
            throw new RuntimeException('Unable to save Events screenshot ticket type: ' . Json::encode($ticketType->getErrors()));
        }
    }
}

// Keep the seeded event in the same complete state as an author who has
// applied their session and ticket-type changes in the control panel.
$event->updateTickets();

Craft::$app->getCache()->flush();
$sessionCount = $countOwned('{{%events_sessions}}', $event->id);
$ticketTypeCount = $countOwned('{{%events_ticket_types}}', $event->id);

$qrCode = QrCode::create('VERBB-EVENTS-DEMO-TICKET-NOT-VALID')
    ->setEncoding(new Encoding('UTF-8'))
    ->setSize(300)
    ->setMargin(0)
    ->setErrorCorrectionLevel(ErrorCorrectionLevel::Medium)
    ->setForegroundColor(new Color(24, 34, 48))
    ->setBackgroundColor(new Color(255, 255, 255));
$qrDataUri = (new PngWriter())->write($qrCode)->getDataUri();

$templateDir = Craft::getAlias('@templates');
FileHelper::createDirectory($templateDir);
$ticketTemplate = str_replace('__QR_DATA_URI__', $qrDataUri, <<<'TWIG'
{# craft-screenshots: sample-frontend #}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Melbourne Design Weekend ticket</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 72px 48px; background: #fff; color: #182230; font-family: Arial, Helvetica, sans-serif; }
        .ticket-wrap { width: min(1120px, 100%); margin: 0 auto; }
        .ticket { display: grid; grid-template-columns: minmax(0, 1fr) 310px; min-height: 360px; overflow: hidden; background: #fff; border: 2px solid #182230; border-radius: 18px; box-shadow: 0 22px 55px rgba(24, 34, 48, .12); }
        .details { display: grid; grid-template-columns: 1.25fr .75fr; gap: 36px 54px; padding: 48px 54px; }
        .event { grid-column: 1 / -1; }
        .label { display: block; margin-bottom: 7px; color: #64748b; font-size: 13px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
        .value { display: block; font-size: 22px; font-weight: 800; line-height: 1.25; }
        .event .value { font-size: 36px; letter-spacing: -.02em; }
        .stub { position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 34px 38px 30px; border-left: 2px dashed #cbd5e1; background: #f8fafc; text-align: center; }
        .stub::before, .stub::after { position: absolute; left: -17px; width: 32px; height: 32px; border: 2px solid #182230; border-radius: 50%; background: #fff; content: ''; }
        .stub::before { top: -18px; }
        .stub::after { bottom: -18px; }
        .qr { width: 160px; height: 160px; margin: 10px 0 13px; image-rendering: pixelated; }
        .code { font-size: 16px; font-weight: 900; letter-spacing: .05em; }
        .attendee { width: 100%; margin-top: 25px; padding-top: 20px; border-top: 1px solid #dce3ea; text-align: left; }
        .attendee .value { margin-top: 3px; font-size: 15px; }
    </style>
</head>
<body>
    <main class="ticket-wrap">
        <article class="ticket" aria-label="Sample Events ticket">
            <section class="details">
                <div class="event"><span class="label">Event</span><span class="value">Melbourne Design Weekend</span></div>
                <div><span class="label">Location</span><span class="value">Royal Exhibition Building</span></div>
                <div><span class="label">Admission</span><span class="value">Weekend pass</span></div>
                <div><span class="label">Starts</span><span class="value">17 Oct 2026<br>10:00 am</span></div>
                <div><span class="label">Ends</span><span class="value">19 Oct 2026<br>5:00 pm</span></div>
            </section>
            <aside class="stub">
                <span class="label">Present at entry</span>
                <img class="qr" src="__QR_DATA_URI__" alt="Inert demonstration QR code">
                <span class="code">#EVENTS26</span>
                <div class="attendee"><span class="label">Ticket holder</span><span class="value">Verbb Guest</span><span class="value">tickets@verbb.io</span></div>
            </aside>
        </article>
    </main>
</body>
</html>
TWIG);
file_put_contents($templateDir . '/events-screenshot-ticket.twig', $ticketTemplate);

echo Json::encode([
    'eventRoute' => '/admin/events/events/' . $eventType->handle . '/' . $event->id . '-' . $event->slug,
    'ticketRoute' => '/events-screenshot-ticket',
    'sessionCount' => $sessionCount,
    'ticketTypeCount' => $ticketTypeCount,
], JSON_THROW_ON_ERROR);
