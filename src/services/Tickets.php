<?php
namespace verbb\events\services;

use verbb\events\elements\Ticket;

use Craft;

use yii\base\Component;

class Tickets extends Component
{
    // Public Methods
    // =========================================================================

    public function getTicketById(int $ticketId, int $siteId = null): ?Ticket
    {
        return Craft::$app->getElements()->getElementById($ticketId, Ticket::class, $siteId);
    }
}
