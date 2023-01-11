<?php
namespace verbb\events\elements\db;

use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\models\EventType;

use Craft;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\elements\User;
use craft\elements\db\ElementQuery;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;

use craft\commerce\db\Table as CommerceTable;

use DateTime;

class EventQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public bool $editable = false;
    public mixed $typeId = null;
    public mixed $startDate = null;
    public mixed $endDate = null;
    public mixed $postDate = null;
    public mixed $expiryDate = null;

    public mixed $before = null;
    public mixed $after = null;
    public mixed $customerId = null;

    protected array $defaultOrderBy = ['events_events.startDate' => SORT_ASC];


    // Public Methods
    // =========================================================================

    public function __construct(string $elementType, array $config = [])
    {
        // Default status
        if (!isset($config['status'])) {
            $config['status'] = Event::STATUS_LIVE;
        }

        parent::__construct($elementType, $config);
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'type':
                $this->type($value);
                break;
            case 'customer':
                $this->customer($value);
                break;
            default:
                parent::__set($name, $value);
        }
    }

    public function type($value): static
    {
        if ($value instanceof EventType) {
            $this->typeId = [$value->id];
        } else if ($value !== null) {
            $this->typeId = (new Query())
                ->select(['id'])
                ->from(['{{%events_eventtypes}}'])
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->typeId = null;
        }

        return $this;
    }

    public function before($value): static
    {
        $this->before = $value;
        return $this;
    }

    public function after($value): static
    {
        $this->after = $value;
        return $this;
    }

    public function editable(bool $value = true): static
    {
        $this->editable = $value;
        return $this;
    }

    public function typeId($value): static
    {
        $this->typeId = $value;
        return $this;
    }

    public function startDate($value): static
    {
        $this->startDate = $value;
        return $this;
    }

    public function endDate($value): static
    {
        $this->endDate = $value;
        return $this;
    }

    public function postDate($value): static
    {
        $this->postDate = $value;
        return $this;
    }

    public function expiryDate($value): static
    {
        $this->expiryDate = $value;
        return $this;
    }

    public function customer(?User $value = null): static
    {
        if ($value) {
            $this->customerId = $value->id;
        } else {
            $this->customerId = null;
        }

        return $this;
    }

    public function customerId($value): static
    {
        $this->customerId = $value;
        return $this;
    }


    // Protected Methods
    // =========================================================================

    protected function beforePrepare(): bool
    {
        $this->_normalizeTypeId();

        // See if 'type' were set to invalid handles
        if ($this->typeId === []) {
            return false;
        }

        $this->joinElementTable('events_events');

        $this->query->select([
            'events_events.id',
            'events_events.typeId',
            'events_events.allDay',
            'events_events.capacity',
            'events_events.startDate',
            'events_events.endDate',
            'events_events.postDate',
            'events_events.expiryDate',
        ]);

        if ($this->startDate) {
            $this->subQuery->andWhere(Db::parseDateParam('events_events.startDate', $this->startDate));
        }

        if ($this->endDate) {
            $this->subQuery->andWhere(Db::parseDateParam('events_events.endDate', $this->endDate));
        }

        if ($this->postDate) {
            $this->subQuery->andWhere(Db::parseDateParam('events_events.postDate', $this->postDate));
        } else {
            if ($this->before) {
                $this->subQuery->andWhere(Db::parseDateParam('events_events.postDate', $this->before, '<'));
            }
            if ($this->after) {
                $this->subQuery->andWhere(Db::parseDateParam('events_events.postDate', $this->after, '>='));
            }
        }

        if ($this->expiryDate) {
            $this->subQuery->andWhere(Db::parseDateParam('events_events.expiryDate', $this->expiryDate));
        }

        if ($this->typeId) {
            $this->subQuery->andWhere(Db::parseParam('events_events.typeId', $this->typeId));
        }

        if ($this->customerId) {
            $this->subQuery->innerJoin('{{%events_tickets}} tickets', '[[tickets.eventId]] = [[events_events.id]]');
            $this->subQuery->innerJoin(CommerceTable::LINEITEMS . ' lineitems', '[[tickets.id]] = [[lineitems.purchasableId]]');
            $this->subQuery->innerJoin(CommerceTable::ORDERS . ' orders', '[[lineitems.orderId]] = [[orders.id]]');
            $this->subQuery->andWhere(['=', '[[orders.customerId]]', $this->customerId]);
            $this->subQuery->andWhere(['=', '[[orders.isCompleted]]', true]);
            $this->subQuery->groupBy(['events_events.id']);
        }

        $this->_applyEditableParam();
        $this->_applyRefParam();

        return parent::beforePrepare();
    }

    protected function statusCondition(string $status): mixed
    {
        $now = new DateTime();
        $now->setTime((int)$now->format('H'), (int)$now->format('i'), 59);
        $currentTimeDb = Db::prepareDateForDb($now);

        return match ($status) {
            Event::STATUS_LIVE => [
                'and',
                [
                    'elements.enabled' => true,
                    'elements_sites.enabled' => true,
                ],
                ['<=', 'events_events.postDate', $currentTimeDb],
                [
                    'or',
                    ['events_events.expiryDate' => null],
                    ['>', 'events_events.expiryDate', $currentTimeDb],
                ],
            ],
            Event::STATUS_PENDING => [
                'and',
                [
                    'elements.enabled' => true,
                    'elements_sites.enabled' => true,
                ],
                ['>', 'events_events.postDate', $currentTimeDb],
            ],
            Event::STATUS_EXPIRED => [
                'and',
                [
                    'elements.enabled' => true,
                    'elements_sites.enabled' => true,
                ],
                ['not', ['events_events.expiryDate' => null]],
                ['<=', 'events_events.expiryDate', $currentTimeDb],
            ],
            default => parent::statusCondition($status),
        };
    }

    // Private Methods
    // =========================================================================

    /**
     * Normalizes the typeId param to an array of IDs or null
     */
    private function _normalizeTypeId(): void
    {
        if (empty($this->typeId)) {
            $this->typeId = null;
        } else if (is_numeric($this->typeId)) {
            $this->typeId = [$this->typeId];
        } else if (!is_array($this->typeId) || !ArrayHelper::isNumeric($this->typeId)) {
            $this->typeId = (new Query())
                ->select(['id'])
                ->from(['{{%events_eventtypes}}'])
                ->where(Db::parseParam('id', $this->typeId))
                ->column();
        }
    }

    private function _applyEditableParam(): void
    {
        if (!$this->editable) {
            return;
        }

        $user = Craft::$app->getUser()->getIdentity();

        if (!$user) {
            throw new QueryAbortedException();
        }

        // Limit the query to only the sections the user has permission to edit
        $this->subQuery->andWhere([
            'events_events.typeId' => Events::$plugin->getEventTypes()->getEditableEventTypeIds(),
        ]);
    }

    private function _applyRefParam(): void
    {
        if (!$this->ref) {
            return;
        }

        $refs = ArrayHelper::toArray($this->ref);
        $joinSections = false;
        $condition = ['or'];

        foreach ($refs as $ref) {
            $parts = array_filter(explode('/', $ref));

            if (!empty($parts)) {
                if (count($parts) == 1) {
                    $condition[] = Db::parseParam('elements_sites.slug', $parts[0]);
                } else {
                    $condition[] = [
                        'and',
                        Db::parseParam('events_eventtypes.handle', $parts[0]),
                        Db::parseParam('elements_sites.slug', $parts[1]),
                    ];
                    $joinSections = true;
                }
            }
        }

        $this->subQuery->andWhere($condition);

        if ($joinSections) {
            $this->subQuery->innerJoin('{{%events_eventtypes}} events_eventtypes', '[[eventtypes.id]] = [[events.typeId]]');
        }
    }
}
