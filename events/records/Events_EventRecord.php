<?php

namespace Craft;

/**
 * @property int      id
 * @property int      typeId
 * @property bool     allDay
 * @property int      capacity
 * @property DateTime startDate
 * @property DateTime endDate
 */
class Events_EventRecord extends BaseRecord
{

    // Public Methods
    // =========================================================================

    public function getTableName()
    {
        return 'events_events';
    }

    public function defineRelations()
    {
        return [
            'element' => [
                static::BELONGS_TO,
                'ElementRecord', 'id',
                'required' => true,
                'onDelete' => static::CASCADE,
            ],
            'type'    => [
                static::BELONGS_TO,
                'Events_EventTypeRecord',
                'onDelete' => static::CASCADE,
            ],
        ];
    }

    // Protected Methods
    // =============================================================================

    protected function defineAttributes()
    {
        return [
            'allDay'    => AttributeType::Bool,
            'capacity'  => AttributeType::Number,
            'startDate' => AttributeType::DateTime,
            'endDate'   => AttributeType::DateTime,
        ];
    }
}
