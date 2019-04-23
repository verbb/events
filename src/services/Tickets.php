<?php
namespace verbb\events\services;

use verbb\events\elements\Event;
use verbb\events\elements\Ticket;
use verbb\events\events\TicketTypeEvent;
use verbb\events\models\TicketType;
use verbb\events\models\TicketTypeSite;
use verbb\events\records\TicketTypeRecord;
use verbb\events\records\TicketTypeSiteRecord;

use Craft;
use craft\db\Query;
use craft\events\SiteEvent;
use craft\helpers\App;
use craft\queue\jobs\ResaveElements;

use yii\base\Component;
use yii\base\Exception;

class Tickets extends Component
{
    // Public Methods
    // =========================================================================

    public function getAllTicketsByEventId(int $eventId, int $siteId = null): array
    {
        $tickets = Ticket::find()->eventId($eventId)->status(null)->limit(null)->siteId($siteId)->all();

        return $tickets;
    }

    public function getTicketById(int $ticketId, int $siteId = null)
    {
        return Craft::$app->getElements()->getElementById($ticketId, Ticket::class, $siteId);
    }
}
