<?php
namespace verbb\events\services;

use verbb\events\elements\Ticket;

use Craft;
use craft\base\ElementInterface;

use yii\base\Component;

class Tickets extends Component
{
    // Public Methods
    // =========================================================================

    public function getAllTicketsByEventId(int $eventId, int $siteId = null): array
    {
        return Ticket::find()->eventId($eventId)->status(null)->limit(null)->siteId($siteId)->all();
    }

    public function getTicketById(int $ticketId, int $siteId = null): ?ElementInterface
    {
        return Craft::$app->getElements()->getElementById($ticketId, Ticket::class, $siteId);
    }
}
