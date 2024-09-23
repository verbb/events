<?php
namespace verbb\events\fieldlayoutelements;

use verbb\events\elements\Event;

use craft\base\ElementInterface;
use craft\fieldlayoutelements\TitleField;
use craft\helpers\Html;

use yii\base\InvalidArgumentException;

class EventTitleField extends TitleField
{
    // Public Methods
    // =========================================================================

    public function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof Event) {
            throw new InvalidArgumentException('EventTitleField can only be used in event field layouts.');
        }

        return parent::inputHtml($element, $static);
    }


    // Protected Methods
    // =========================================================================

    protected function selectorInnerHtml(): string
    {
        return
            Html::tag('span', '', [
                'class' => ['fld-event-title-field-icon', 'fld-field-hidden', 'hidden'],
            ]) .
            parent::selectorInnerHtml();
    }
}
