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

    protected array $defaultOrderBy = ['events_events.postDate' => SORT_DESC];


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
        if ($name === 'type') {
            $this->type($value);
        } else {
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
                ->from(['{{%events_event_types}}'])
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->typeId = null;
        }

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

    public function status(array|string|null $value): static
    {
        parent::status($value);
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
            'events_events.capacity',
            'events_events.postDate',
            'events_events.expiryDate',
            'events_events.ticketsCache',
        
            // Get the earliest startDate and latest endDate from the sessions table
            'sessions.startDate',
            'sessions.endDate',
        ]);

        // Subquery for sessions aggregation, to get around GROUPBY issues
        $sessionsQuery = (new Query())
            ->select([
                'primaryOwnerId AS eventId',
                'MIN(startDate) AS startDate',
                'MAX(endDate) AS endDate',
            ])
            ->from('{{%events_sessions}}')
            ->innerJoin('{{%elements}} elements', '[[elements.id]] = [[events_sessions.id]]')
            ->where([
                // Only count live and non-deleted session elements
                'elements.enabled' => true,
                'elements.dateDeleted' => null,
            ])
            ->groupBy('primaryOwnerId');

        $this->query->leftJoin(['sessions' => $sessionsQuery], '[[sessions.eventId]] = [[events_events.id]]');

        if (isset($this->typeId)) {
            $this->subQuery->andWhere(['events_events.typeId' => $this->typeId]);
        }

        if (isset($this->startDate)) {
            $this->query->andWhere(Db::parseDateParam('sessions.startDate', $this->startDate));
        }

        if (isset($this->endDate)) {
            $this->query->andWhere(Db::parseDateParam('sessions.endDate', $this->endDate));
        }

        $this->_applyEditableParam();
        $this->_applyRefParam();

        return parent::beforePrepare();
    }

    protected function statusCondition(string $status): mixed
    {
        $currentTimeDb = Db::prepareDateForDb(new DateTime());

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

    private function _normalizeTypeId(): void
    {
        if (empty($this->typeId)) {
            $this->typeId = is_array($this->typeId) ? [] : null;
        } else if (is_numeric($this->typeId)) {
            $this->typeId = [$this->typeId];
        } else if (!is_array($this->typeId) || !ArrayHelper::isNumeric($this->typeId)) {
            $this->typeId = (new Query())
                ->select(['id'])
                ->from(['{{%events_event_types}}'])
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
                        Db::parseParam('events_event_types.handle', $parts[0]),
                        Db::parseParam('elements_sites.slug', $parts[1]),
                    ];
                    
                    $joinSections = true;
                }
            }
        }

        $this->subQuery->andWhere($condition);

        if ($joinSections) {
            $this->subQuery->innerJoin('{{%events_event_types}} events_event_types', '[[events_event_types.id]] = [[events.typeId]]');
        }
    }
}
