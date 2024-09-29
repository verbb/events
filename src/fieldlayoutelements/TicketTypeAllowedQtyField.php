<?php
namespace verbb\events\fieldlayoutelements;

use verbb\events\elements\PurchasedTicket;
use verbb\events\elements\TicketType;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use craft\helpers\Cp;
use craft\helpers\Html;

use yii\base\InvalidArgumentException;

class TicketTypeAllowedQtyField extends BaseNativeField
{
    // Properties
    // =========================================================================

    public bool $required = false;
    public bool $mandatory = false;
    public string $attribute = 'allowedQty';


    // Protected Methods
    // =========================================================================

    protected function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('events', 'Allowed Qty');
    }

    protected function defaultInstructions(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('events', 'The minimum or maximum allowed quantity customers must purchase.');
    }

    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        if (!($element instanceof TicketType || $element instanceof PurchasedTicket)) {
            throw new InvalidArgumentException(static::class . ' can only be used in ticket field layouts.');
        }

        return Html::beginTag('div', ['class' => 'flex']) .
            Html::beginTag('div', ['class' => 'textwrapper']) .
                Cp::textHtml([
                    'id' => 'minQty',
                    'name' => 'minQty',
                    'value' => $element->minQty,
                    'placeholder' => Craft::t('events', 'Any'),
                    'title' => Craft::t('events', 'Minimum allowed quantity'),
                ]) .
            Html::endTag('div') .
            Html::tag('div', Craft::t('events', 'to'), ['class' => 'label light']) .
            Html::beginTag('div', ['class' => 'textwrapper']) .
                Cp::textHtml([
                    'id' => 'maxQty',
                    'name' => 'maxQty',
                    'value' => $element->maxQty,
                    'placeholder' => Craft::t('events', 'Any'),
                    'title' => Craft::t('events', 'Maximum allowed quantity'),
                ]) .
            Html::endTag('div') .
        Html::endTag('div');
    }
}
