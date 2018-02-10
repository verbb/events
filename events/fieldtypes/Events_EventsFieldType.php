<?php
namespace Craft;

class Events_EventsFieldType extends BaseElementFieldType
{
    // Properties
    // =========================================================================

    protected $elementType = 'Events_Event';

    // Public Methods
    // =========================================================================

    public function getName()
    {
        return Craft::t('Events');
    }

    // Protected Methods
    // =========================================================================

    protected function getAddButtonLabel()
    {
        return Craft::t('Add an event');
    }
}
