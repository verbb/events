<?php
namespace verbb\events\fieldlayoutelements;

use verbb\events\elements\PurchasedTicket;
use verbb\events\elements\TicketType;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use craft\helpers\Cp;

use yii\base\InvalidArgumentException;

class TicketTypePriceField extends BaseNativeField
{
    // Properties
    // =========================================================================

    public bool $required = false;
    public bool $mandatory = true;
    public string $attribute = 'price';


    // Protected Methods
    // =========================================================================

    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('events', 'Price');
    }

    protected function defaultInstructions(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('events', 'The price of the ticket.');
    }

    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        if (!($element instanceof TicketType || $element instanceof PurchasedTicket)) {
            throw new InvalidArgumentException(static::class . ' can only be used in ticket field layouts.');
        }

        return Cp::moneyInputHtml([
            'id' => 'price',
            'name' => 'price',
            'value' => $element->price,
        ]);
    }
}
