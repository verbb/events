<?php
namespace verbb\events\migrations;

use verbb\events\elements\PurchasedTicket;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\helpers\Db;

class m260708_000000_purchased_ticket_reservation_status extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%events_purchased_tickets}}')) {
            return true;
        }

        if (!$this->db->columnExists('{{%events_purchased_tickets}}', 'reservationStatus')) {
            $this->addColumn('{{%events_purchased_tickets}}', 'reservationStatus', $this->string(32)->notNull()->defaultValue('active')->after('checkedInDate'));
        }

        if (!$this->db->columnExists('{{%events_purchased_tickets}}', 'cancelledAt')) {
            $this->addColumn('{{%events_purchased_tickets}}', 'cancelledAt', $this->dateTime()->after('reservationStatus'));
        }

        if (!$this->db->columnExists('{{%events_purchased_tickets}}', 'cancelledReason')) {
            $this->addColumn('{{%events_purchased_tickets}}', 'cancelledReason', $this->text()->after('cancelledAt'));
        }

        if (!$this->db->columnExists('{{%events_purchased_tickets}}', 'cancelledById')) {
            $this->addColumn('{{%events_purchased_tickets}}', 'cancelledById', $this->integer()->after('cancelledReason'));
            $this->addForeignKey(null, '{{%events_purchased_tickets}}', 'cancelledById', CraftTable::USERS, 'id', 'SET NULL', null);
        }

        $this->createIndex(null, '{{%events_purchased_tickets}}', 'reservationStatus', false);

        $this->_migrateTrashedPurchasedTickets();

        return true;
    }

    public function safeDown(): bool
    {
        if (!$this->db->tableExists('{{%events_purchased_tickets}}')) {
            return true;
        }

        if ($this->db->columnExists('{{%events_purchased_tickets}}', 'cancelledById')) {
            Db::dropForeignKeyIfExists('{{%events_purchased_tickets}}', 'cancelledById', $this);
            $this->dropColumn('{{%events_purchased_tickets}}', 'cancelledById');
        }

        foreach (['cancelledReason', 'cancelledAt', 'reservationStatus'] as $column) {
            if ($this->db->columnExists('{{%events_purchased_tickets}}', $column)) {
                $this->dropColumn('{{%events_purchased_tickets}}', $column);
            }
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _migrateTrashedPurchasedTickets(): void
    {
        $rows = (new Query())
            ->select(['pt.id', 'el.dateDeleted'])
            ->from(['pt' => '{{%events_purchased_tickets}}'])
            ->innerJoin(['el' => CraftTable::ELEMENTS], '[[el.id]] = [[pt.id]]')
            ->where(['not', ['el.dateDeleted' => null]])
            ->all();

        if ($rows === []) {
            return;
        }

        foreach ($rows as $row) {
            $this->update('{{%events_purchased_tickets}}', [
                'reservationStatus' => PurchasedTicket::RESERVATION_STATUS_CANCELLED,
                'cancelledAt' => $row['dateDeleted'],
                'cancelledReason' => 'Migrated from trashed purchased ticket.',
            ], ['id' => $row['id']], [], false);
        }

        $elementsService = Craft::$app->getElements();

        foreach ($rows as $row) {
            $purchasedTicket = $elementsService->getElementById($row['id'], PurchasedTicket::class);

            if ($purchasedTicket?->dateDeleted) {
                $elementsService->restoreElement($purchasedTicket);
            }
        }
    }
}
