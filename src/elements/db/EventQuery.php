<?php
namespace verbb\events\elements\db;

use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\models\EventType;

use Craft;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\db\Table;
use craft\elements\db\ElementQuery;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;

use yii\db\Expression;

use DateTime;

class EventQuery extends ElementQuery
{
    // Traits
    // =========================================================================

    use ElementIndexQueryTrait;


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
        } elseif ($value !== null) {
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
        // Prevent this from running in Craft's `m250315_131608_unlimited_authors` migration before our upgrade
        if (!Craft::$app->getDb()->tableExists('{{%events_sessions}}')) {
            return false;
        }

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

            // The earliest start and latest end of the event's live sessions, looked up for just the returned events.
            'startDate' => $this->_sessionDateQuery('MIN', 'startDate'),
            'endDate' => $this->_sessionDateQuery('MAX', 'endDate'),
        ]);

        if (isset($this->typeId)) {
            $this->subQuery->andWhere(['events_events.typeId' => $this->typeId]);
        }

        if (isset($this->startDate)) {
            $this->subQuery->andWhere(Db::parseDateParam('sessions.startDate', $this->startDate));
        }

        if (isset($this->endDate)) {
            $this->subQuery->andWhere(Db::parseDateParam('sessions.endDate', $this->endDate));
        }

        $this->_applyEditableParam();
        $this->_applyRefParam();

        return parent::beforePrepare();
    }

    protected function afterPrepare(): bool
    {
        // The whole-table session aggregate is only joined where something filters or sorts on session dates, or refers
        // to the `sessions` alias directly. Other event queries, the default `postDate` order included, skip it.
        if (isset($this->startDate) || isset($this->endDate) || $this->_referencesSessionDates($this->subQuery)) {
            $this->subQuery->leftJoin(['sessions' => $this->_sessionsAggregateQuery()], '[[sessions.eventId]] = [[events_events.id]]');
        }

        // The main query selects session dates through correlated lookups for just the returned events. Anything that
        // sorts or filters on them (or replaces the select, as `ids()` does) gets the joined columns, as before.
        if ($this->_referencesSessionDates($this->query)) {
            $this->query->leftJoin(['sessions' => $this->_sessionsAggregateQuery()], '[[sessions.eventId]] = [[events_events.id]]');

            foreach (['startDate', 'endDate'] as $column) {
                if (($this->query->select[$column] ?? null) instanceof Query) {
                    $this->query->select[$column] = "sessions.$column";
                }
            }
        }

        // Ensure startDate/endDate sort is applied in the subQuery so pagination returns the
        // correct slice of all events (e.g. 100 earliest vs 100 latest), not just re-sorting
        // the current page. Qualify column names for the joined sessions table.
        if (is_array($this->subQuery->orderBy)) {
            $orderBy = $this->subQuery->orderBy;

            foreach (['startDate' => 'sessions.startDate', 'endDate' => 'sessions.endDate'] as $attr => $qualified) {
                if (isset($orderBy[$attr])) {
                    $dir = $orderBy[$attr];
                    unset($orderBy[$attr]);
                    $orderBy[$qualified] = $dir;
                }
            }

            $this->subQuery->orderBy = $orderBy;
        }

        return parent::afterPrepare();
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
        } elseif (is_numeric($this->typeId)) {
            $this->typeId = [$this->typeId];
        } elseif (!is_array($this->typeId) || !ArrayHelper::isNumeric($this->typeId)) {
            $this->typeId = (new Query())
                ->select(['id'])
                ->from(['{{%events_event_types}}'])
                ->where(Db::parseParam('id', $this->typeId))
                ->column();
        }
    }

    private function _sessionsAggregateQuery(): Query
    {
        // Subquery for sessions aggregation, to get around GROUP BY issues
        return (new Query())
            ->select([
                'primaryOwnerId AS eventId',
                'MIN([[startDate]]) AS startDate',
                'MAX([[endDate]]) AS endDate',
            ])
            ->from('{{%events_sessions}} events_sessions')
            ->innerJoin('{{%elements}} elements', '[[elements.id]] = [[events_sessions.id]]')
            ->where([
                // Only count live and non-deleted session elements
                'elements.enabled' => true,
                'elements.dateDeleted' => null,
            ])
            ->groupBy('primaryOwnerId');
    }

    private function _sessionDateQuery(string $aggregate, string $column): Query
    {
        // Matches `_sessionsAggregateQuery()` for one event, using the `primaryOwnerId` index.
        return (new Query())
            ->select(new Expression("$aggregate([[sessionDates.$column]])"))
            ->from(['sessionDates' => '{{%events_sessions}}'])
            ->innerJoin(['sessionElements' => Table::ELEMENTS], '[[sessionElements.id]] = [[sessionDates.id]]')
            ->where('[[sessionDates.primaryOwnerId]] = [[events_events.id]]')
            ->andWhere([
                'sessionElements.enabled' => true,
                'sessionElements.dateDeleted' => null,
            ]);
    }

    private function _referencesSessionDates(Query $query): bool
    {
        // Session dates were always joined as `sessions`, and were the only `startDate`/`endDate` columns in scope, so
        // custom conditions and orders may name either. Select keys are skipped, as they're our own aliases.
        $parts = [array_values($query->select ?? []), $query->where, $query->having, $query->groupBy, $query->orderBy];

        foreach ($query->join ?? [] as $join) {
            $parts[] = $join[2] ?? null;
        }

        return $this->_containsPattern($parts, '/\bsessions\.|\b(startDate|endDate)\b/i');
    }

    private function _containsPattern(mixed $value, string $pattern): bool
    {
        if (is_string($value)) {
            return (bool)preg_match($pattern, $value);
        }

        if ($value instanceof Expression) {
            return (bool)preg_match($pattern, $value->expression);
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                if ((is_string($key) && preg_match($pattern, $key)) || $this->_containsPattern($item, $pattern)) {
                    return true;
                }
            }
        }

        return false;
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

        $refs = is_string($this->ref) ? StringHelper::split($this->ref) : ArrayHelper::toArray($this->ref);
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
            $this->subQuery->innerJoin('{{%events_event_types}} events_event_types', '[[events_event_types.id]] = [[events_events.typeId]]');
        }
    }
}
