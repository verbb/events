<?php
namespace verbb\events\elements\db;

use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\models\EventType;

use Craft;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\elements\db\ElementQuery;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;

use craft\commerce\db\Table as CommerceTable;
use craft\commerce\models\Customer;

use DateTime;
use yii\db\Connection;

class EventQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public $editable = false;
    public $typeId;
    public $startDate;
    public $endDate;
    public $postDate;
    public $expiryDate;

    public $before;
    public $after;
    public $customerId;

    protected $defaultOrderBy = ['events_events.startDate' => SORT_ASC];


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

    public function type($value)
    {
        if ($value instanceof EventType) {
            $this->typeId = $value->id;
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

    public function before($value)
    {
        $this->before = $value;
        return $this;
    }

    public function after($value)
    {
        $this->after = $value;
        return $this;
    }

    public function editable(bool $value = true)
    {
        $this->editable = $value;
        return $this;
    }

    public function typeId($value)
    {
        $this->typeId = $value;
        return $this;
    }

    public function startDate($value)
    {
        $this->startDate = $value;
        return $this;
    }

    public function endDate($value)
    {
        $this->endDate = $value;
        return $this;
    }

    public function postDate($value)
    {
        $this->postDate = $value;
        return $this;
    }

    public function expiryDate($value)
    {
        $this->expiryDate = $value;
        return $this;
    }

    public function status($value)
    {
        return parent::status($value);
    }

    public function customer(Customer $value = null)
    {
        if ($value) {
            $this->customerId = $value->id;
        } else {
            $this->customerId = null;
        }

        return $this;
    }

    public function customerId($value)
    {
        $this->customerId = $value;
        return $this;
    }


    // Protected Methods
    // =========================================================================

    protected function beforePrepare(): bool
    {
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

    protected function statusCondition(string $status)
    {
        $currentTimeDb = Db::prepareDateForDb(new \DateTime());

        switch ($status) {
            case Event::STATUS_LIVE:
                return [
                    'and',
                    [
                        'elements.enabled' => true,
                        'elements_sites.enabled' => true
                    ],
                    ['<=', 'events_events.postDate', $currentTimeDb],
                    [
                        'or',
                        ['events_events.expiryDate' => null],
                        ['>', 'events_events.expiryDate', $currentTimeDb]
                    ]
                ];
            case Event::STATUS_PENDING:
                return [
                    'and',
                    [
                        'elements.enabled' => true,
                        'elements_sites.enabled' => true,
                    ],
                    ['>', 'events_events.postDate', $currentTimeDb]
                ];
            case Event::STATUS_EXPIRED:
                return [
                    'and',
                    [
                        'elements.enabled' => true,
                        'elements_sites.enabled' => true
                    ],
                    ['not', ['events_events.expiryDate' => null]],
                    ['<=', 'events_events.expiryDate', $currentTimeDb]
                ];
            default:
                return parent::statusCondition($status);
        }
    }

    // Private Methods
    // =========================================================================

    private function _applyEditableParam()
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
            'events_events.typeId' => Events::$plugin->getEventTypes()->getEditableEventTypeIds()
        ]);
    }

    private function _applyRefParam()
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
                        Db::parseParam('elements_sites.slug', $parts[1])
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
