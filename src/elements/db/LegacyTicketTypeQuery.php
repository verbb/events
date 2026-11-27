<?php
namespace verbb\events\elements\db;

use craft\elements\db\ElementQuery;

class LegacyTicketTypeQuery extends ElementQuery
{
    // Protected Methods
    // =========================================================================

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('events_legacy_ticket_types');

        $this->query->select([
            'events_legacy_ticket_types.id',
        ]);

        return parent::beforePrepare();
    }
}
