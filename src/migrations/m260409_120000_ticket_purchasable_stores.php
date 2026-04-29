<?php
namespace verbb\events\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table as CraftTable;
use verbb\events\elements\Ticket;
use verbb\events\helpers\TicketPurchasableStoreSync;

class m260409_120000_ticket_purchasable_stores extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%commerce_purchasables_stores}}')) {
            return true;
        }

        if (!$this->db->tableExists('{{%events_tickets}}')) {
            return true;
        }

        // Backfill commerce_purchasables_stores for ticket purchasables (Commerce 5 manual order picker).
        $ticketIds = (new Query())
            ->select(['t.id'])
            ->from(['t' => '{{%events_tickets}}'])
            ->innerJoin(['e' => CraftTable::ELEMENTS], '[[e.id]] = [[t.id]]')
            ->where([
                'e.type' => Ticket::class,
                'e.revisionId' => null,
                'e.draftId' => null,
            ])
            ->andWhere(['e.dateDeleted' => null])
            ->column();

        foreach ($ticketIds as $ticketId) {
            TicketPurchasableStoreSync::syncTicketIdToAllStores((int)$ticketId);
        }

        return true;
    }

    public function safeDown(): bool
    {
        return true;
    }
}
