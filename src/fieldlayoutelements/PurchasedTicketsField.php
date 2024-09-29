<?php
namespace verbb\events\fieldlayoutelements;

use verbb\events\elements\Event;
use verbb\events\elements\Session;
use verbb\events\elements\TicketType;

use Craft;
use craft\base\ElementInterface;
use craft\enums\ElementIndexViewMode;
use craft\fieldlayoutelements\BaseNativeField;

use yii\base\InvalidArgumentException;

class PurchasedTicketsField extends BaseNativeField
{
    // Properties
    // =========================================================================

    public bool $mandatory = false;
    public string $attribute = 'purchasedTickets';


    // Public Methods
    // =========================================================================

    public function hasCustomWidth(): bool
    {
        return false;
    }


    // Protected Methods
    // =========================================================================

    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('events', 'Purchased Tickets');
    }

    protected function defaultInstructions(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('events', 'View all the purchased tickets for this {owner}.', ['owner' => $element::lowerDisplayName()]);
    }

    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        if (!($element instanceof Event || $element instanceof Session || $element instanceof TicketType)) {
            throw new InvalidArgumentException(static::class . ' can only be used in event field layouts.');
        }

        if ($element->getIsDraft()) {
            return null;
        }

        return $element->getPurchasedTicketManager($element)->getIndexHtml($element, [
            'allowedViewModes' => [ElementIndexViewMode::Table],
            'inlineEditable' => false,
        ]);
    }
}
