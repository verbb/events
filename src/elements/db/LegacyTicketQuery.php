<?php
namespace verbb\events\elements\db;

use craft\elements\db\ElementQuery;

class LegacyTicketQuery extends ElementQuery
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
