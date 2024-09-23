<?php
namespace verbb\events\fieldlayoutelements;

use verbb\events\elements\Session;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use craft\helpers\Cp;

use yii\base\InvalidArgumentException;

class SessionStartDateTimeField extends BaseNativeField
{
    // Properties
    // =========================================================================

    public bool $required = true;
    public bool $mandatory = true;
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

    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof Session) {
            throw new InvalidArgumentException('SessionStartDateTimeField can only be used in session field layouts.');
        }

        return Cp::dateTimeFieldHtml([
            'id' => 'start-date',
            'name' => 'startDate',
            'value' => $element->startDate ? $element->startDate->format('c') : null,
        ]);
    }
}
