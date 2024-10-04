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

class TicketTypePromotableField extends BaseNativeField
{
    // Properties
    // =========================================================================

    public bool $required = false;
    public bool $mandatory = false;
    public string $attribute = 'promotable';


    // Protected Methods
    // =========================================================================

    protected function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('events', 'Promotable');
    }

    protected function defaultInstructions(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('events', 'Whether discounts and sales should be applied to tickets.');
    }

    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        if (!($element instanceof TicketType || $element instanceof PurchasedTicket)) {
            throw new InvalidArgumentException(static::class . ' can only be used in ticket field layouts.');
        }

        return Cp::lightswitchHtml([
            'id' => 'promotable',
            'name' => 'promotable',
            'small' => true,
            'on' => $element->promotable,
        ]);
    }
}
