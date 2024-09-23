<?php
namespace verbb\events\fieldlayoutelements;

use verbb\events\elements\Event;

use Craft;
use craft\base\ElementInterface;
use craft\enums\ElementIndexViewMode;
use craft\fieldlayoutelements\BaseNativeField;

use yii\base\InvalidArgumentException;

class TicketsField extends BaseNativeField
{
    // Properties
    // =========================================================================

    public bool $mandatory = false;
    public string $attribute = 'tickets';


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
        return Craft::t('events', 'Tickets');
    }

    protected function defaultInstructions(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('events', 'Tickets are auto-generated for an event, based on your sessions and ticket types.');
    }

    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof Event) {
            throw new InvalidArgumentException('TicketsField can only be used in event field layouts.');
        }

        if ($element->getIsDraft()) {
            return null;
        }

        return $element->getTicketManager()->getIndexHtml($element, [
            'allowedViewModes' => [ElementIndexViewMode::Table],
            'inlineEditable' => false,
        ]);
    }
}
