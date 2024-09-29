<?php
namespace verbb\events\fieldlayoutelements;

use verbb\events\elements\Event;

use Craft;
use craft\base\ElementInterface;
use craft\enums\ElementIndexViewMode;
use craft\fieldlayoutelements\BaseNativeField;

use yii\base\InvalidArgumentException;

class SessionsField extends BaseNativeField
{
    // Properties
    // =========================================================================

    public bool $mandatory = true;
    public string $attribute = 'sessions';


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
        return Craft::t('events', 'Sessions');
    }

    protected function defaultInstructions(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('events', 'Define the dates and times for this event.');
    }

    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof Event) {
            throw new InvalidArgumentException(static::class . ' can only be used in event field layouts.');
        }

        Craft::$app->getView()->registerDeltaName($this->attribute());

        return $element->getSessionManager()->getIndexHtml($element, [
            'canCreate' => true,
            'allowedViewModes' => [ElementIndexViewMode::Table],
            'sortable' => false,
            'fieldLayouts' => [$element->getType()->getSessionFieldLayout()],
        ]);
    }
}
