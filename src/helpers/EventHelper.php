<?php
namespace verbb\events\helpers;

use verbb\events\Events;
use verbb\events\elements\Event as EventModel;
use verbb\events\elements\Ticket;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\helpers\Localization as LocalizationHelper;

use yii\web\HttpException;

class EventHelper
{
    // Static Methods
    // =========================================================================

    public static function populateEventTicketModel(EventModel $event, $ticket, $key): Ticket
    {
        $eventId = $event->id;

        $newTicket = str_starts_with($key, 'new');

        if ($eventId && !$newTicket) {
            $ticketModel = Events::$plugin->getTickets()->getTicketById((int)$key, $event->siteId);

            if (!$ticketModel) {
                $ticketModel = new Ticket();
            }
        } else {
            $ticketModel = new Ticket();
        }

        $ticketModel->setEvent($event);

        // Quantity can be null, zero or a number, but ensure it's cast correctly
        $ticketModel->enabled = (bool)($ticket['enabled'] ?? $ticketModel->enabled);
        $quantity = $ticket['quantity'] ?? null;
        $quantity = strlen($quantity) ? (int)$quantity : $ticketModel->quantity;

        $ticketModel->typeId = $ticket['typeIds'][0] ?? $ticketModel->typeId;
        $ticketModel->enabled = (bool)($ticket['enabled'] ?? $ticketModel->enabled);
        $ticketModel->sku = $ticket['sku'] ?? $ticketModel->sku;
        $ticketModel->quantity = $quantity;
        $ticketModel->price = (float)LocalizationHelper::normalizeNumber($ticket['price'] ?? $ticketModel->price);

        if (($availableFrom = ($ticket['availableFrom'] ?? null)) !== null) {
            $ticketModel->availableFrom = DateTimeHelper::toDateTime($availableFrom) ?: null;
        }

        if (($availableTo = ($ticket['availableTo'] ?? null)) !== null) {
            $ticketModel->availableTo = DateTimeHelper::toDateTime($availableTo) ?: null;
        }

        if (isset($ticket['fields'])) {
            $ticketModel->setFieldValues($ticket['fields']);
        }

        if (!empty($ticket['title'])) {
            $ticketModel->title = $ticket['title'];
        }

        return $ticketModel;
    }

    public static function populateEventFromPost(): EventModel
    {
        $request = Craft::$app->getRequest();
        $eventId = $request->getParam('eventId');
        $siteId = $request->getParam('siteId');

        if ($eventId) {
            $event = Events::$plugin->getEvents()->getEventById($eventId, $siteId);

            if (!$event) {
                throw new HttpException(404, Craft::t('events', 'No event with the ID “{id}”', ['id' => $eventId]));
            }
        } else {
            $event = new EventModel();
        }

        $event->typeId = $request->getParam('typeId');
        $event->siteId = $siteId ?? $event->siteId;

        $event->title = $request->getParam('title', $event->title);
        $event->slug = $request->getParam('slug');
        $event->enabled = (bool)$request->getParam('enabled');
        $event->enabledForSite = (bool)$request->getParam('enabledForSite', $event->enabledForSite);

        $event->allDay = (bool)$request->getParam('allDay');
        $event->capacity = intval($request->getParam('capacity'));

        if (($startDate = $request->getParam('startDate')) !== null) {
            $event->startDate = DateTimeHelper::toDateTime($startDate) ?: null;
        }

        if (($endDate = $request->getParam('endDate')) !== null) {
            $event->endDate = DateTimeHelper::toDateTime($endDate) ?: null;
        }

        if (($postDate = $request->getParam('postDate')) !== null) {
            $event->postDate = DateTimeHelper::toDateTime($postDate) ?: null;
        }

        if (($expiryDate = $request->getParam('expiryDate')) !== null) {
            $event->expiryDate = DateTimeHelper::toDateTime($expiryDate) ?: null;
        }

        $event->setFieldValuesFromRequest('fields');

        $event->setTickets($request->getParam('tickets', []));

        return $event;
    }
}
