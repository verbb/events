<?php
namespace verbb\events\elements\db;

use craft\elements\db\ElementQuery;

use verbb\base\elements\db\CachedElementQuery;

class LegacyTicketQuery extends CachedElementQuery
{
    // Protected Methods
    // =========================================================================

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('events_legacy_tickets');

        $this->query->select([
            'events_legacy_tickets.id',
        ]);

        return parent::beforePrepare();
    }
}
