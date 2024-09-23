<?php
namespace verbb\events\fieldlayoutelements;

use verbb\events\elements\Session;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use craft\helpers\Cp;

use yii\base\InvalidArgumentException;

class SessionAllDayField extends BaseNativeField
{
    // Properties
    // =========================================================================

    public bool $mandatory = true;
    public string $attribute = 'allDay';


    // Protected Methods
    // =========================================================================

    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('events', 'All Day');
    }

    protected function defaultInstructions(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('events', 'Does this session run for the entire day?');
    }

    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof Session) {
            throw new InvalidArgumentException('SessionAllDayField can only be used in session field layouts.');
        }

        return Cp::lightswitchHtml([
            'id' => 'all-day',
            'name' => 'allDay',
            'on' => $element->allDay,
        ]);
    }
}
