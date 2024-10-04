<?php
namespace verbb\events\elements\conditions\events;

use verbb\events\Events;
use verbb\events\elements\db\EventQuery;
use verbb\events\elements\Event;
use verbb\events\models\EventType;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;

use yii\base\InvalidConfigException;

class EventTypeConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    // Public Methods
    // =========================================================================

    public function getLabel(): string
    {
        return Craft::t('events', 'Event Type');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['type'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        $eventTypes = Events::$plugin->getEventTypes()->getAllEventTypes();

        $value = $this->paramValue(function(string $value) use ($eventTypes) {
            return ArrayHelper::firstWhere($eventTypes, 'uid', $value)?->handle;
        });

        $query->type($value);
    }

    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->getType()->uid);
    }


    // Protected Methods
    // =========================================================================

    protected function options(): array
    {
        return collect(Events::$plugin->getEventTypes()->getAllEventTypes())
            ->map(fn(EventType $eventType) => ['value' => $eventType->uid, 'label' => $eventType->name])
            ->all();
    }
}
