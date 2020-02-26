<?php
namespace verbb\events\migrations;

use verbb\events\Events;
use verbb\events\elements\PurchasedTicket;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\queue\jobs\ResaveElements;

use craft\commerce\Plugin as Commerce;

class m200225_000000_purchased_tickets_line_item_options extends Migration
{
    public function safeUp()
    {
        $db = Craft::$app->getDb();

        $purchasedTickets = PurchasedTicket::find()
            ->all();

        foreach ($purchasedTickets as $purchasedTicket) {
            $lineItem = $purchasedTicket->lineItem;

            if (!$lineItem) {
                continue;
            }

            $ticket = $purchasedTicket->ticket;

            if (!$ticket) {
                continue;
            }

            // Set the field values from the ticket (handle defaults, and values set on the ticket)
            $purchasedTicket->setFieldValues($ticket->getFieldValues());

            // But also allow overriding through the line item options
            foreach ($lineItem->options as $option => $value) {
                // Just catch any errors when trying to set attributes that aren't field handles
                try {
                    $purchasedTicket->setFieldValue($option, $value);
                } catch (\Throwable $e) {
                    continue;
                }
            }

            Craft::$app->getElements()->saveElement($purchasedTicket, false);
        }
    }

    public function safeDown()
    {
        echo "m200225_000000_purchased_tickets_line_item_options cannot be reverted.\n";
        return false;
    }
}
