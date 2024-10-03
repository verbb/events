<?php
namespace verbb\events\fieldlayoutelements;

use verbb\events\elements\Event;

use Craft;
use craft\base\ElementInterface;
use craft\enums\ElementIndexViewMode;
use craft\fieldlayoutelements\BaseNativeField;

use yii\base\InvalidArgumentException;

class TicketTypesField extends BaseNativeField
{
    // Properties
    // =========================================================================

    public bool $mandatory = true;
    public string $attribute = 'ticketTypes';


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
        return Craft::t('events', 'Ticket Types');
    }

    protected function defaultInstructions(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('events', 'Define the types of tickets this event should have.');
    }

    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof Event) {
            throw new InvalidArgumentException(static::class . ' can only be used in event field layouts.');
        }

        if (!$element->canViewTicketTypes()) {
            return null;
        }
        
        Craft::$app->getView()->registerDeltaName($this->attribute());

        return $element->getTicketTypeManager()->getIndexHtml($element, [
            'canCreate' => $element->canCreateTicketTypes(),
            'allowedViewModes' => [ElementIndexViewMode::Table],
            'sortable' => true,
            'fieldLayouts' => [$element->getType()->getTicketTypeFieldLayout()],
        ]);
    }
}
