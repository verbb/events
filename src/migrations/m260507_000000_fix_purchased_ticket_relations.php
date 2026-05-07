<?php
namespace verbb\events\migrations;

use craft\db\Migration;
use craft\db\Query;

class m260507_000000_fix_purchased_ticket_relations extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (
            !$this->db->tableExists('{{%events_purchased_tickets}}') ||
            !$this->db->tableExists('{{%events_tickets}}') ||
            !$this->db->tableExists('{{%commerce_lineitems}}')
        ) {
            return true;
        }

        foreach (['legacyTicketId', 'lineItemId', 'ticketId', 'ticketTypeId', 'sessionId', 'eventId'] as $column) {
            if (!$this->db->columnExists('{{%events_purchased_tickets}}', $column)) {
                return true;
            }
        }

        // Repair sites where the Craft 5 migration copied the literal string `ticketId`
        // instead of the legacy ticket ID, preventing the later relation backfill.
        $rows = (new Query())
            ->select([
                'pt.id',
                'ticketId' => 't.id',
                'ticketTypeId' => 't.typeId',
                't.sessionId',
                't.eventId',
                't.legacyTicketId',
            ])
            ->from(['pt' => '{{%events_purchased_tickets}}'])
            ->innerJoin(['li' => '{{%commerce_lineitems}}'], '[[li.id]] = [[pt.lineItemId]]')
            ->innerJoin(['t' => '{{%events_tickets}}'], '[[t.id]] = [[li.purchasableId]]')
            ->where([
                'or',
                ['pt.ticketId' => null],
                ['pt.ticketTypeId' => null],
                ['pt.sessionId' => null],
                ['pt.eventId' => null],
                ['pt.legacyTicketId' => 'ticketId'],
            ])
            ->all();

        foreach ($rows as $row) {
            $this->update('{{%events_purchased_tickets}}', [
                'eventId' => $row['eventId'],
                'sessionId' => $row['sessionId'],
                'ticketId' => $row['ticketId'],
                'ticketTypeId' => $row['ticketTypeId'],
                'legacyTicketId' => $row['legacyTicketId'],
            ], ['id' => $row['id']]);
        }

        return true;
    }

    public function safeDown(): bool
    {
        return true;
    }
}
