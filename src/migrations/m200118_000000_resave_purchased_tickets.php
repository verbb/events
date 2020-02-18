<?php
namespace verbb\events\migrations;

use verbb\events\elements\PurchasedTicket;

use Craft;
use craft\db\Migration;
use craft\queue\jobs\ResaveElements;

class m200118_000000_resave_purchased_tickets extends Migration
{
    public function safeUp()
    {
        Craft::$app->getQueue()->push(new ResaveElements([
            'elementType' => PurchasedTicket::class
        ]));

        return true;
    }

    public function safeDown()
    {
        echo "m200118_000000_resave_purchased_tickets cannot be reverted.\n";
        return false;
    }
}
