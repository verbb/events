<?php
namespace verbb\events\fieldlayoutelements;

use verbb\events\elements\TicketType;

use craft\base\ElementInterface;
use craft\fieldlayoutelements\TitleField;
use craft\helpers\Html;

use yii\base\InvalidArgumentException;

class TicketTypeTitleField extends TitleField
{
    // Public Methods
    // =========================================================================

    public function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof TicketType) {
            throw new InvalidArgumentException('TicketTitleField can only be used in ticket field layouts.');
        }

        return parent::inputHtml($element, $static);
    }


    // Protected Methods
    // =========================================================================

    protected function selectorInnerHtml(): string
    {
        return Html::tag('span', '', [
            'class' => ['fld-ticket-title-field-icon', 'fld-field-hidden', 'hidden'],
        ]) . parent::selectorInnerHtml();
    }
}
