<?php
namespace verbb\events\fieldlayoutelements;

use verbb\events\elements\PurchasedTicket;
use verbb\events\elements\TicketType;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use craft\helpers\Cp;

use yii\base\InvalidArgumentException;

class TicketTypeSeatsPerTicketField extends BaseNativeField
{
    // Properties
    // =========================================================================

    public bool $required = false;
    public bool $mandatory = false;
    public string $attribute = 'seatsPerTicket';


    // Protected Methods
    // =========================================================================

    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('events', 'Seats Per Ticket');
    }

    protected function defaultInstructions(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('events', 'How many seats a single ticket occupies. This will reflect the capacity when purchased.');
    }

    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        if (!($element instanceof TicketType || $element instanceof PurchasedTicket)) {
            throw new InvalidArgumentException(static::class . ' can only be used in ticket field layouts.');
        }

        return Cp::textHtml([
            'id' => 'seatsPerTicket',
            'name' => 'seatsPerTicket',
            'value' => $element->seatsPerTicket,
        ]);
    }
}
