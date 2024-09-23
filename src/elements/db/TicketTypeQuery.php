<?php
namespace verbb\events\elements\db;

use verbb\events\elements\Event;
use verbb\events\elements\TicketTypeCollection;

use Craft;
use craft\base\ElementInterface;
use craft\db\Table;
use craft\elements\db\ElementQuery;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;

use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\db\Connection;

class TicketTypeQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public mixed $hasEvent = null;
    public mixed $primaryOwnerId = null;
    public mixed $price = null;
    public mixed $capacity = null;
    public mixed $ownerId = null;
    public mixed $eventId = null;

    protected array $defaultOrderBy = ['elements_owners.sortOrder' => SORT_ASC];


    // Public Methods
    // =========================================================================

    public function __set($name, $value)
    {
        switch ($name) {
            case 'event':
                $this->event($value);
                break;
            case 'eventId':
                // Added due to the removal of the `$eventId` property
                $this->ownerId($value);
                break;
            case 'owner':
                $this->owner($value);
                break;
            case 'primaryOwner':
                $this->primaryOwner($value);
                break;
            default:
                parent::__set($name, $value);
        }
    }

    public function event(mixed $value): static
    {
        if ($value instanceof Event) {
            $this->ownerId = [$value->id];
        } else {
            $this->ownerId = $value;
        }
        return $this;
    }

    public function owner(mixed $value): static
    {
        if ($value instanceof ElementInterface) {
            $this->ownerId = [$value->id];
        } else {
            $this->ownerId = $value;
        }
        return $this;
    }

    public function primaryOwner(mixed $value): static
    {
        if ($value instanceof ElementInterface) {
            $this->primaryOwnerId = [$value->id];
        } else {
            $this->primaryOwnerId = $value;
        }
        return $this;
    }

    public function price(mixed $value): static
    {
        $this->price = $value;
        return $this;
    }

    public function capacity(mixed $value): static
    {
        $this->capacity = $value;
        return $this;
    }

    public function eventId(mixed $value): static
    {
        $this->ownerId = $value;
        return $this;
    }

    public function primaryOwnerId(mixed $value): static
    {
        $this->primaryOwnerId = $value;
        return $this;
    }

    public function ownerId(mixed $value): static
    {
        $this->ownerId = $value;
        return $this;
    }

    public function hasEvent(mixed $value): static
    {
        $this->hasEvent = $value;
        return $this;
    }

    public function collect(?Connection $db = null): TicketTypeCollection
    {
        return TicketTypeCollection::make(parent::collect($db));
    }


    // Protected Methods
    // =========================================================================

    protected function beforePrepare(): bool
    {
        try {
            $this->primaryOwnerId = $this->_normalizeOwnerId($this->primaryOwnerId);
        } catch (InvalidArgumentException) {
            throw new InvalidConfigException('Invalid primaryOwnerId param value');
        }

        try {
            $this->ownerId = $this->_normalizeOwnerId($this->ownerId);
        } catch (InvalidArgumentException) {
            throw new InvalidConfigException('Invalid ownerId param value');
        }

        $this->joinElementTable('events_ticket_types');

        $this->query->select([
            'events_ticket_types.id',
            'events_ticket_types.primaryOwnerId',
            'events_ticket_types.price',
            'events_ticket_types.capacity',
            'events_ticket_types.availableFrom',
            'events_ticket_types.availableTo',
            'events_elements_sites.slug as eventSlug',
            'events_event_types.handle as eventTypeHandle',
        ]);

        // Join in the elements_owners table
        $ownersCondition = [
            'and',
            '[[elements_owners.elementId]] = [[elements.id]]',
            $this->ownerId ? ['elements_owners.ownerId' => $this->ownerId] : '[[elements_owners.ownerId]] = [[events_ticket_types.primaryOwnerId]]',
        ];

        $this->query
            ->addSelect([
                'elements_owners.ownerId',
                'elements_owners.sortOrder',
            ])
            ->innerJoin(['elements_owners' => Table::ELEMENTS_OWNERS], $ownersCondition);

        $this->subQuery->innerJoin(['elements_owners' => Table::ELEMENTS_OWNERS], $ownersCondition);

        if ($this->primaryOwnerId) {
            $this->subQuery->andWhere(['events_ticket_types.primaryOwnerId' => $this->primaryOwnerId]);
        }

        $this->query->leftJoin('{{%events_events}} events_events', '[[elements_owners.ownerId]] = [[events_events.id]]');
        $this->query->leftJoin('{{%events_event_types}} events_event_types', '[[events_events.typeId]] = [[events_event_types.id]]');
        $this->query->leftJoin(Table::ELEMENTS_SITES . ' events_elements_sites', '[[elements_owners.ownerId]] = [[events_elements_sites.elementId]] and [[events_elements_sites.siteId]] =  [[elements_sites.siteId]]');

        $this->subQuery->leftJoin('{{%events_events}} events_events', '[[elements_owners.ownerId]] = [[events_events.id]]');
        $this->subQuery->leftJoin('{{%events_event_types}} events_event_types', '[[events_events.typeId]] = [[events_event_types.id]]');

        if (isset($this->typeId)) {
            $this->subQuery->andWhere(Db::parseParam('events_events.typeId', $this->typeId));
        }

        if (isset($this->eventId)) {
            $this->subQuery->andWhere(['events_ticket_definitions.primaryOwnerId' => $this->eventId]);
        }

        if (isset($this->price)) {
            $this->subQuery->andWhere(Db::parseParam('events_ticket_types.price', $this->price));
        }

        if (isset($this->capacity)) {
            $this->subQuery->andWhere(Db::parseParam('events_ticket_types.capacity', $this->capacity));
        }

        $this->_applyHasEventParam();

        return parent::beforePrepare();
    }

    protected function cacheTags(): array
    {
        $tags = [];

        if ($this->ownerId) {
            foreach ($this->ownerId as $ownerId) {
                $tags[] = "event:$ownerId";
            }
        }

        return $tags;
    }


    // Private Methods
    // =========================================================================

    private function _normalizeOwnerId(mixed $value): ?array
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return [$value];
        }

        if (!is_array($value) || !ArrayHelper::isNumeric($value)) {
            throw new InvalidArgumentException();
        }

        return $value;
    }

    private function _applyHasEventParam(): void
    {
        if (!isset($this->hasEvent)) {
            return;
        }

        if ($this->hasEvent instanceof EventQuery) {
            $eventQuery = $this->hasEvent;
        } elseif (is_array($this->hasEvent)) {
            $eventQuery = Event::find();
            $eventQuery = Craft::configure($eventQuery, $this->hasEvent);
        } else {
            return;
        }

        $eventQuery->limit = null;
        $eventQuery->select('events_events.id');

        // Remove any blank product IDs (if any)
        $eventQuery->andWhere(['not', ['events_events.id' => null]]);

        $this->subQuery->andWhere(['events_sessions.primaryOwnerId' => $eventQuery]);
    }
}
