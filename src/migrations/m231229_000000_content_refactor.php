<?php
namespace verbb\events\migrations;

use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\elements\PurchasedTicket;
use verbb\events\elements\Ticket;
use verbb\events\elements\TicketType;

use craft\db\Query;
use craft\migrations\BaseContentRefactorMigration;

class m231229_000000_content_refactor extends BaseContentRefactorMigration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        foreach (Events::$plugin->getEventTypes()->getAllEventTypes() as $type) {
            $this->updateElements(
                (new Query())->from('{{%events_events}}')->where(['typeId' => $type->id]),
                $type->getFieldLayout(),
            );
        }

        foreach (Events::$plugin->getTicketTypes()->getAllTicketTypes() as $type) {
            $this->updateElements(
                (new Query())->from('{{%events_tickets}}')->where(['typeId' => $type->id]),
                $type->getFieldLayout(),
            );
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m231229_000000_content_refactor cannot be reverted.\n";

        return false;
    }
}
