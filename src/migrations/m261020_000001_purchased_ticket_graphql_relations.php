<?php
namespace verbb\events\migrations;

use Craft;
use craft\db\Migration;

class m261020_000001_purchased_ticket_graphql_relations extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        Craft::$app->getGql()->invalidateCaches();

        return true;
    }

    public function safeDown(): bool
    {
        return true;
    }
}
