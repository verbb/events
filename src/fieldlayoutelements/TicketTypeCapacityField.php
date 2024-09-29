<?php
namespace verbb\events\fieldlayoutelements;

use verbb\events\elements\PurchasedTicket;
use verbb\events\elements\TicketType;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use craft\helpers\Cp;

use yii\base\InvalidArgumentException;

class TicketTypeCapacityField extends BaseNativeField
{
    // Properties
    // =========================================================================

    public bool $required = false;
    public bool $mandatory = true;
    public string $attribute = 'capacity';


    // Protected Methods
    // =========================================================================

    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('events', 'Capacity');
    }

    protected function defaultInstructions(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('events', 'How many tickets are available to purchase.');
    }

    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        if (!($element instanceof TicketType || $element instanceof PurchasedTicket)) {
            throw new InvalidArgumentException(static::class . ' can only be used in ticket field layouts.');
        }

        return Cp::textHtml([
            'id' => 'capacity',
            'name' => 'capacity',
            'value' => $element->capacity,
        ]);
    }
}
