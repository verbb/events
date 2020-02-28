<?php
namespace verbb\events\migrations;

use verbb\events\Events;
use verbb\events\elements\PurchasedTicket;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\queue\jobs\ResaveElements;

class m200224_000000_purchased_tickets_content extends Migration
{
    public function safeUp()
    {
        $db = Craft::$app->getDb();

        $purchasedTickets = (new Query())
            ->select(['*'])
            ->from('{{%events_purchasedtickets}}')
            ->all();

        foreach ($purchasedTickets as $purchasedTicket) {
            if (!$purchasedTicket['ticketId']) {
                continue;
            }

            $ticket = Events::$plugin->getTickets()->getTicketById($purchasedTicket['ticketId']);

            if (!$ticket) {
                continue;
            }

            $fieldLayout = $ticket->getFieldLayout();

            if (!$fieldLayout) {
                continue;
            }

            $db->createCommand()
                ->update(Table::ELEMENTS, ['fieldLayoutId' => $fieldLayout->id], ['id' => $purchasedTicket['id']])
                ->execute();

            $contentData = [
                'elementId' => $purchasedTicket['id'],
                'siteId' => Craft::$app->getSites()->getPrimarySite()->id,
            ];

            $db->createCommand()
                ->upsert(Table::CONTENT, $contentData)
                ->execute();
        }
    }

    public function safeDown()
    {
        echo "m200224_000000_purchased_tickets_content cannot be reverted.\n";
        return false;
    }
}
