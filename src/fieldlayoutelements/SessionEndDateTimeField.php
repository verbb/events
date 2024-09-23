<?php
namespace verbb\events\fieldlayoutelements;

use verbb\events\elements\Session;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use craft\helpers\Cp;

use yii\base\InvalidArgumentException;

class SessionEndDateTimeField extends BaseNativeField
{
    // Properties
    // =========================================================================

    public bool $required = true;
    public bool $mandatory = true;
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

    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof Session) {
            throw new InvalidArgumentException('SessionEndDateTimeField can only be used in session field layouts.');
        }

        return Cp::dateTimeFieldHtml([
            'id' => 'end-date',
            'name' => 'endDate',
            'value' => $element->endDate ? $element->endDate->format('c') : null,
        ]);
    }
}
