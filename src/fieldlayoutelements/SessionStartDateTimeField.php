<?php
namespace verbb\events\fieldlayoutelements;

use verbb\events\elements\Session;

use Craft;
use craft\base\ElementInterface;

class SessionStartDateTimeField extends SessionDateTimeField
{
    // Properties
    // =========================================================================

    public string $attribute = 'startDate';


    // Protected Methods
    // =========================================================================

    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('events', 'Start Date');
    }

    protected function defaultInstructions(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('events', 'The start date/time for the session.');
    }
}
