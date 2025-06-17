<?php
namespace verbb\events\elements\db;

use craft\elements\db\ElementQuery;

use verbb\base\elements\db\CachedElementQuery;

class LegacyTicketTypeQuery extends CachedElementQuery
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
