<?php
namespace verbb\events\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;

class SessionEndDateTimeField extends SessionDateTimeField
{
    // Properties
    // =========================================================================

    public string $attribute = 'endDate';


    // Protected Methods
    // =========================================================================

    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('events', 'End Date');
    }

    protected function defaultInstructions(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('events', 'The end date/time for the session.');
    }
}
