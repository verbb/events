<?php
namespace verbb\events\elements;

use verbb\events\Events;
use verbb\events\assetbundles\EventEditAsset;
use verbb\events\elements\db\EventQuery;
use verbb\events\elements\db\SessionQuery;
use verbb\events\elements\db\TicketQuery;
use verbb\events\elements\db\TicketTypeQuery;
use verbb\events\elements\traits\PurchasedTicketTrait;
use verbb\events\models\EventType;
use verbb\events\records\Event as EventRecord;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\db\Table;
use craft\elements\db\EagerLoadPlan;
use craft\elements\db\ElementQueryInterface;
use craft\elements\NestedElementManager;
use craft\elements\User;
use craft\enums\PropagationMethod;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;

use yii\base\Exception;
use yii\base\InvalidConfigException;

use Illuminate\Support\Collection;

use DateTime;

class Event extends Element
{
    // Constants
    // =========================================================================

    public const STATUS_LIVE = 'live';
    public const STATUS_PENDING = 'pending';
    public const STATUS_EXPIRED = 'expired';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('events', 'Event');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('events', 'Events');
    }

    public static function refHandle(): ?string
    {
        return 'event';
    }

    public static function hasDrafts(): bool
    {
        return true;
    }

    public static function trackChanges(): bool
    {
        return true;
    }

    public static function hasTitles(): bool
    {
        return true;
    }

    public static function hasUris(): bool
    {
        return true;
    }

    public static function isLocalized(): bool
    {
        return true;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_LIVE => Craft::t('events', 'Live'),
            self::STATUS_PENDING => Craft::t('events', 'Pending'),
            self::STATUS_EXPIRED => Craft::t('events', 'Expired'),
            self::STATUS_DISABLED => Craft::t('events', 'Disabled'),
        ];
    }

    public static function find(): ElementQueryInterface
    {
        return new EventQuery(static::class);
    }

    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
    {
        if ($handle == 'sessions') {
            $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

            $map = (new Query())
                ->select('ownerId as source, elementId as target')
                ->from(Table::ELEMENTS_OWNERS)
                ->where(['ownerId' => $sourceElementIds])
                ->orderBy('sortOrder asc')
                ->all();

            return [
                'elementType' => Session::class,
                'map' => $map,
            ];
        }

        if ($handle == 'ticketTypes') {
            $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

            $map = (new Query())
                ->select('ownerId as source, elementId as target')
                ->from(Table::ELEMENTS_OWNERS)
                ->where(['ownerId' => $sourceElementIds])
                ->orderBy('sortOrder asc')
                ->all();

            return [
                'elementType' => TicketType::class,
                'map' => $map,
            ];
        }

        return parent::eagerLoadingMap($sourceElements, $handle);
    }

    public static function gqlTypeNameByContext(mixed $context): string
    {
        return $context->handle . '_Event';
    }

    public static function gqlScopesByContext(mixed $context): array
    {
        return ['eventTypes.' . $context->uid];
    }

    public static function prepElementQueryForTableAttribute(ElementQueryInterface $elementQuery, string $attribute): void
    {
        if ($attribute === 'sessions') {
            $elementQuery->andWith('sessions');
        } else if ($attribute === 'ticketTypes') {
            $elementQuery->andWith('ticketTypes');
        } else {
            parent::prepElementQueryForTableAttribute($elementQuery, $attribute);
        }
    }

    protected static function defineSources(string $context = null): array
    {
        if ($context == 'index') {
            $eventTypes = Events::$plugin->getEventTypes()->getEditableEventTypes();
            $editable = true;
        } else {
            $eventTypes = Events::$plugin->getEventTypes()->getAllEventTypes();
            $editable = false;
        }

        $eventTypeIds = [];

        foreach ($eventTypes as $eventType) {
            $eventTypeIds[] = $eventType->id;
        }

        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('events', 'All events'),
                'criteria' => [
                    'typeId' => $eventTypeIds,
                    'editable' => $editable,
                ],
                'defaultSort' => ['postDate', 'desc'],
            ],
        ];

        $sources[] = ['heading' => Craft::t('events', 'Event Types')];

        foreach ($eventTypes as $eventType) {
            $key = 'eventType:' . $eventType->uid;
            $canEditEvents = Craft::$app->getUser()->checkPermission('events-editEventType:' . $eventType->uid);

            $sources[] = [
                'key' => $key,
                'label' => Craft::t('site', $eventType->name),
                'data' => [
                    'handle' => $eventType->handle,
                    'editable' => $canEditEvents,
                ],
                'criteria' => [
                    'typeId' => $eventType->id,
                    'editable' => $editable,
                ],
                // Get site ids enabled for this product type
                'sites' => $eventType->getSiteIds(),
            ];
        }

        return $sources;
    }

    protected static function defineFieldLayouts(?string $source): array
    {
        if ($source === null || $source === '*') {
            $eventTypes = Events::$plugin->getEventTypes()->getAllEventTypes();
        } else {
            $eventTypes = [];

            if (preg_match('/^eventType:(.+)$/', $source, $matches)) {
                $eventType = Events::$plugin->getEventTypes()->getEventTypeByUid($matches[1]);
                
                if ($eventType) {
                    $eventTypes[] = $eventType;
                }
            }
        }

        return array_map(fn(EventType $eventType) => $eventType->getFieldLayout(), $eventTypes);
    }

    protected static function includeSetStatusAction(): bool
    {
        return true;
    }

    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('events', 'Title'),
            [
                'label' => Craft::t('events', 'Post Date'),
                'orderBy' => 'postDate',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('events', 'Expiry Date'),
                'orderBy' => 'expiryDate',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'ID'),
                'orderBy' => 'elements.id',
                'attribute' => 'id',
            ],
        ];
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'title' => ['label' => Craft::t('events', 'Event')],
            'status' => ['label' => Craft::t('events', 'Status')],
            'id' => ['label' => Craft::t('events', 'ID')],
            'type' => ['label' => Craft::t('events', 'Type')],
            'slug' => ['label' => Craft::t('events', 'Slug')],
            'uri' => ['label' => Craft::t('events', 'URI')],
            'postDate' => ['label' => Craft::t('events', 'Post Date')],
            'expiryDate' => ['label' => Craft::t('events', 'Expiry Date')],
            'link' => ['label' => Craft::t('events', 'Link'), 'icon' => 'world'],
            'dateCreated' => ['label' => Craft::t('events', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('events', 'Date Updated')],
            'sessions' => ['label' => Craft::t('events', 'Sessions')],
            'ticketTypes' => ['label' => Craft::t('events', 'Ticket Types')],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = [];

        if ($source == '*') {
            $attributes[] = 'type';
        }

        $attributes[] = 'status';
        $attributes[] = 'postDate';
        $attributes[] = 'expiryDate';
        $attributes[] = 'link';

        return $attributes;
    }



    // Traits
    // =========================================================================

    use PurchasedTicketTrait;

    

    // Properties
    // =========================================================================

    public ?int $capacity = null;
    public ?DateTime $postDate = null;
    public ?DateTime $expiryDate = null;
    public ?int $typeId = null;
    public ?string $ticketsCache = null;

    public bool $updateTickets = false;
    public ?DateTime $startDate = null;
    public ?DateTime $endDate = null;

    private ?EventType $_eventType = null;
    private ?SessionCollection $_sessions = null;
    private ?NestedElementManager $_sessionManager = null;
    private ?TicketTypeCollection $_ticketTypes = null;
    private ?NestedElementManager $_ticketTypeManager = null;
    private ?TicketCollection $_tickets = null;
    private ?NestedElementManager $_ticketManager = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        unset($config['allDay']);

        parent::__construct($config);
    }

    public function __toString(): string
    {
        return (string)$this->title;
    }

    public function setAttributes($values, $safeOnly = true): void
    {
        // This is needed for Craft.NestedElementManager::markAsDirty()
        if (isset($values['sessions']) && $values['sessions'] === '*') {
            $this->setDirtyAttributes(['sessions']);
            unset($values['sessions']);
        }

        if (isset($values['tickets']) && $values['tickets'] === '*') {
            $this->setDirtyAttributes(['tickets']);
            unset($values['tickets']);
        }

        if (isset($values['ticketTypes']) && $values['ticketTypes'] === '*') {
            $this->setDirtyAttributes(['ticketTypes']);
            unset($values['ticketTypes']);
        }

        parent::setAttributes($values, $safeOnly);
    }

    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }

        try {
            $eventType = $this->getType();
        } catch (Exception) {
            return false;
        }

        return $user->can('events-editEventType:' . $eventType->uid);
    }

    public function canSave(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }

        try {
            $eventType = $this->getType();
        } catch (Exception) {
            return false;
        }

        return $user->can('events-editEventType:' . $eventType->uid);
    }

    public function canDuplicate(User $user): bool
    {
        if (parent::canDuplicate($user)) {
            return true;
        }

        try {
            $eventType = $this->getType();
        } catch (Exception) {
            return false;
        }

        return $user->can('events-editEventType:' . $eventType->uid);
    }

    public function canDelete(User $user): bool
    {
        if (parent::canDelete($user)) {
            return true;
        }

        try {
            $eventType = $this->getType();
        } catch (Exception) {
            return false;
        }

        return $user->can('events-deleteEvents:' . $eventType->uid);
    }

    public function canDeleteForSite(User $user): bool
    {
        return Craft::$app->getElements()->canDelete($this, $user);
    }

    public function createAnother(): ?ElementInterface
    {
        return null;
    }

    public function canCreateDrafts(User $user): bool
    {
        // Everyone with view permissions can create drafts
        return true;
    }

    public function setScenario($value): void
    {
        foreach ($this->getSessions() as $session) {
            $session->setScenario($value);
        }

        foreach ($this->getTicketTypes() as $ticketType) {
            $ticketType->setScenario($value);
        }

        parent::setScenario($value);
    }

    public function hasRevisions(): bool
    {
        return $this->getType()->enableVersioning;
    }

    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('events/events');
    }

    public function getFieldLayout(): ?FieldLayout
    {
        $fieldLayout = parent::getFieldLayout();

        if ($fieldLayout) {
            return $fieldLayout;
        }

        $fieldLayout = $this->getType()->getFieldLayout();

        if ($fieldLayout->id) {
            $this->fieldLayoutId = $fieldLayout->id;
            return $fieldLayout;
        }

        return null;
    }

    public function getUriFormat(): ?string
    {
        $eventTypeSiteSettings = $this->getType()->getSiteSettings();

        if (!isset($eventTypeSiteSettings[$this->siteId])) {
            throw new InvalidConfigException('The "' . $this->getType()->name . '" event type is not enabled for the "' . $this->getSite()->name . '" site.');
        }

        return $eventTypeSiteSettings[$this->siteId]->uriFormat;
    }

    public function getType(): EventType
    {
        if ($this->_eventType !== null) {
            return $this->_eventType;
        }

        if ($this->typeId === null) {
            throw new InvalidConfigException('Event is missing its event type ID');
        }

        $eventType = Events::$plugin->getEventTypes()->getEventTypeById($this->typeId);

        if ($eventType === null) {
            throw new InvalidConfigException('Invalid event type ID: ' . $this->typeId);
        }

        return $this->_eventType = $eventType;
    }

    public function getTotalCapacity(): ?int
    {
        // Check if we have overridden the capacity at the event level
        if ($this->capacity) {
            return $this->capacity;
        }

        // Thank you Laravel Collections!
        return $this->getTicketTypes()->sum('capacity');
    }

    public function getSessions(bool $includeDisabled = false): SessionCollection
    {
        if (!isset($this->_sessions)) {
            if (!$this->id) {
                return SessionCollection::make();
            }

            $this->_sessions = self::createSessionQuery($this)->status(null)->collect();
        }

        return $this->_sessions->filter(fn(Session $session) => $includeDisabled || $session->enabled);
    }

    public function setSessions(SessionCollection|SessionQuery|array $sessions): void
    {
        if ($sessions instanceof SessionQuery) {
            $this->_sessions = null;
            return;
        }

        $this->_sessions = $sessions instanceof SessionCollection ? $sessions : SessionCollection::make($sessions);
    }

    public function getSessionManager(): NestedElementManager
    {
        if (!isset($this->_sessionManager)) {
            $this->_sessionManager = new NestedElementManager(Session::class, fn(Event $event) => self::createSessionQuery($event), [
                'attribute' => 'sessions',
                'propagationMethod' => PropagationMethod::All,
                'valueGetter' => fn(Event $event) => $event->getSessions(true),
            ]);
        }

        return $this->_sessionManager;
    }

    public function getTicketTypes(bool $includeDisabled = false): TicketTypeCollection
    {
        if (!isset($this->_ticketTypes)) {
            if (!$this->id) {
                return TicketTypeCollection::make();
            }

            $this->_ticketTypes = self::createTicketTypeQuery($this)->status(null)->collect();
        }

        return $this->_ticketTypes->filter(fn(TicketType $ticketType) => $includeDisabled || $ticketType->enabled);
    }

    public function setTicketTypes(TicketTypeCollection|TicketTypeQuery|array $ticketTypes): void
    {
        if ($ticketTypes instanceof TicketTypeQuery) {
            $this->_ticketTypes = null;
            return;
        }

        $this->_ticketTypes = $ticketTypes instanceof TicketTypeCollection ? $ticketTypes : TicketTypeCollection::make($ticketTypes);
    }

    public function getTicketTypeManager(): NestedElementManager
    {
        if (!isset($this->_ticketTypeManager)) {
            $this->_ticketTypeManager = new NestedElementManager(TicketType::class, fn(Event $event) => self::createTicketTypeQuery($event), [
                'attribute' => 'ticketTypes',
                'propagationMethod' => PropagationMethod::All,
                'valueGetter' => fn() => $this->getTicketTypes(true),
            ]);
        }

        return $this->_ticketTypeManager;
    }

    public function getTickets(bool $includeDisabled = false): TicketCollection
    {
        if (!isset($this->_tickets)) {
            if (!$this->id) {
                return TicketCollection::make();
            }

            $this->_tickets = self::createTicketQuery($this)->status(null)->collect();
        }

        return $this->_tickets->filter(fn(Ticket $ticket) => $includeDisabled || $ticket->enabled);
    }

    public function setTickets(TicketCollection|TicketQuery|array $tickets): void
    {
        if ($tickets instanceof TicketQuery) {
            $this->_tickets = null;
            return;
        }

        $this->_tickets = $tickets instanceof TicketCollection ? $tickets : TicketCollection::make($tickets);
    }

    public function getTicketManager(): NestedElementManager
    {
        if (!isset($this->_ticketManager)) {
            $this->_ticketManager = new NestedElementManager(Ticket::class, fn(Event $event) => self::createTicketQuery($event), [
                'attribute' => 'tickets',
                'propagationMethod' => PropagationMethod::All,
                'valueGetter' => fn() => $this->getTickets(true),
                'ownerIdParam' => 'eventId',
                'primaryOwnerIdParam' => 'eventId',
            ]);
        }

        return $this->_ticketManager;
    }

    public function getStatus(): ?string
    {
        $status = parent::getStatus();

        if ($status == self::STATUS_ENABLED && $this->postDate) {
            $currentTime = DateTimeHelper::currentTimeStamp();
            $postDate = $this->postDate->getTimestamp();
            $expiryDate = $this->expiryDate?->getTimestamp();

            if ($postDate <= $currentTime && ($expiryDate === null || $expiryDate > $currentTime)) {
                return self::STATUS_LIVE;
            }

            if ($postDate > $currentTime) {
                return self::STATUS_PENDING;
            }

            return self::STATUS_EXPIRED;
        }

        return $status;
    }

    public function setEagerLoadedElements(string $handle, array $elements, EagerLoadPlan $plan): void
    {
        if ($handle == 'tickets') {
            $this->setTickets($elements);
        } else if ($handle == 'ticketTypes') {
            $this->setTicketTypes($elements);
        } else if ($handle == 'sessions') {
            $this->setSessions($elements);
        } else {
            parent::setEagerLoadedElements($handle, $elements, $plan);
        }
    }

    public function hasPendingTicketChanges(): bool
    {
        return !($this->getTicketCacheKey() === $this->ticketsCache);
    }

    public function getTicketCacheKey(): string
    {
        // We only need to update tickets when session or ticket types have been added or removed.
        $sessionIds = Session::find()->eventId($this->id)->ids();
        $ticketTypeIds = TicketType::find()->eventId($this->id)->ids();

        // But, include tickets, in case they've been deleted through other means
        $ticketIds = Ticket::find()->eventId($this->id)->ids();

        $data = array_merge($sessionIds, $ticketTypeIds, $ticketIds);

        return hash('sha256', Json::encode($data));
    }

    public function getSidebarHtml(bool $static): string
    {
        $view = Craft::$app->getView();
        $containerId = 'events-event-pane';
        $id = $view->namespaceInputId($containerId);

        $view->registerAssetBundle(EventEditAsset::class);

        $js = <<<JS
            (() => { new Craft.Events.EventEdit('$id'); })();
        JS;

        $view->registerJs($js, $view::POS_END);

        $html = Html::tag('div', null, ['id' => $containerId]);

        $html .= parent::getSidebarHtml($static);

        $sessions = Session::find()->eventId($this->id)->exists();
        $ticketTypes = TicketType::find()->eventId($this->id)->exists();

        if ($sessions && $ticketTypes && $this->getIsCanonical()) {
            if ($this->hasPendingTicketChanges()) {
                $ticketStatusField = Html::beginTag('div', [
                    'class' => 'meta',
                    'style' => [
                        'background-color' => 'var(--yellow-050) !important',
                        'box-shadow' => '0 0 0 1px #f6d9b8, 0 2px 12px rgba(205, 216, 228, .5)',
                        'padding' => '1rem 1.5rem',
                    ],
                ]) . 
                    Html::beginTag('div') . 
                        Html::beginTag('div', [
                            'style' => [
                                'display' => 'flex',
                            ],
                        ]) . 
                            Html::tag('span', null, [
                                'class' => 'icon',
                                'aria-hidden' => true,
                                'data-icon' => 'alert',
                                'style' => [
                                    'color' => 'var(--yellow-400)',
                                    'margin-top' => '-1px',
                                    'display' => 'block',
                                    'margin-right' => '0.5rem',
                                ],
                            ]) . 
                            Html::tag('div', Craft::t('events', 'Pending ticket updates'), [
                                'style' => [
                                    'color' => 'var(--yellow-800)',
                                    'font-weight' => '500',
                                ],
                            ]) . 
                        Html::endTag('div') . 
                        Html::beginTag('div', [
                            'style' => [
                                'padding' => '0.5rem 0',
                            ],
                        ]) . 
                            Html::tag('p', Craft::t('events', 'Changes to this event have affected your tickets and updates should be applied.'), [
                                'style' => [
                                    'color' => 'var(--yellow-700)',
                                ],
                            ]) . 
                        Html::endTag('div') . 

                        Html::submitButton(Craft::t('events', 'Apply ticket updates'), [
                            'class' => 'formsubmit btn',
                            'data-redirect' => Craft::$app->getSecurity()->hashData('{cpEditUrl}'),
                            'data-params' => [
                                'updateTickets' => true,
                            ],
                            'style' => [
                                'color' => '#fff',
                                'background-color' => 'var(--yellow-700)',
                            ],
                        ]) . 
                    Html::endTag('div') . 
                Html::endTag('div');
            } else {
                $ticketStatusField = Html::beginTag('div', [
                    'class' => 'meta',
                    'style' => [
                        'background-color' => 'var(--green-050) !important',
                        'box-shadow' => '0 0 0 1px #c7e5d2, 0 2px 12px rgba(205, 216, 228, .5)',
                        'padding' => '1rem 1.5rem',
                    ],
                ]) . 
                    Html::beginTag('div', [
                        'style' => [
                            'color' => 'var(--green-700)',
                            'align-items' => 'flex-start',
                            'display' => 'flex',
                            'flex-wrap' => 'nowrap',
                        ],
                    ]) . 
                        Html::tag('span', null, [
                            'class' => 'icon',
                            'aria-hidden' => true,
                            'data-icon' => 'circle-check',
                            'style' => [
                                'color' => 'var(--green-500)',
                                'flex-shrink' => '1',
                                'margin-top' => '-1px',
                                'margin-right' => '0.5rem',
                            ],
                        ]) . 
                        Html::tag('span', Craft::t('events', 'No pending ticket updates.')) . 
                    Html::endTag('div') . 
                Html::endTag('div');
            }

            $html .= Html::beginTag('fieldset', ['class' => 'field']) .
                Html::tag('legend', Craft::t('events', 'Ticket Status'), ['class' => 'h6']) .
                Html::tag('div', $ticketStatusField) .
                Html::endTag('fieldset');
        }

        return $html;
    }

    public function updateTickets(): void
    {
        // Query both sessions and ticket types, including their disabled items to sync tickets against
        $sessions = Session::find()->eventId($this->id)->status(null)->collect();
        $ticketTypes = TicketType::find()->eventId($this->id)->status(null)->collect();

        $elementsService = Craft::$app->getElements();

        // Fetch all tickets for this event here for performance. Remember to query disabled tickets too.
        $currentTickets = Ticket::find()->eventId($this->id)->status(null)->collect();

        // Keep track of any processed tickets to assist with deletion
        $validTicketIds = Collection::make();

        foreach ($sessions as $session) {
            foreach ($ticketTypes as $ticketType) {
                // Find an existing ticket, we don't need to update, as tickets just maintain a reference to things
                $ticket = $currentTickets->first(function(Ticket $ticket) use ($session, $ticketType) {
                    return $ticket->sessionId === $session->id && $ticket->typeId === $ticketType->id;
                });

                if ($ticket) {
                    // Sync all tickets with their related session/ticket types disabled state. Both have to be enabled for a ticket to be enabled
                    $newStatus = $session->enabled && $ticketType->enabled;

                    if ($ticket->enabled !== $newStatus) {
                        Db::update('{{%elements}}', ['enabled' => $newStatus], ['id' => $ticket->id]);
                    }

                    $validTicketIds[] = $ticket->id;

                    continue;
                }

                $ticket = new Ticket([
                    'eventId' => $this->id,
                    'sessionId' => $session->id,
                    'typeId' => $ticketType->id,
                ]);

                $elementsService->saveElement($ticket);

                $validTicketIds[] = $ticket->id;
            }
        }

        // Delete tickets that are no longer valid
        $invalidTickets = $currentTickets->filter(function(Ticket $ticket) use ($validTicketIds) {
            return !$validTicketIds->contains($ticket->id);
        });

        foreach ($invalidTickets as $ticket) {
            $elementsService->deleteElement($ticket);
        }

        Db::update('{{%events_events}}', ['ticketsCache' => $this->getTicketCacheKey()], ['id' => $this->id]);
    }

    public function beforeSave(bool $isNew): bool
    {
        // Make sure the event has at least one revision if the event type has versioning enabled
        if ($this->_shouldSaveRevision()) {
            $hasRevisions = self::find()
                ->revisionOf($this)
                ->site('*')
                ->status(null)
                ->exists();

            if (!$hasRevisions) {
                /** @var self|null $currentProduct */
                $currentProduct = self::find()
                    ->id($this->id)
                    ->site('*')
                    ->status(null)
                    ->one();

                // May be null if the event is currently stored as an unpublished draft
                if ($currentProduct) {
                    $revisionNotes = 'Revision from ' . Craft::$app->getFormatter()->asDatetime($currentProduct->dateUpdated);
                    Craft::$app->getRevisions()->createRevision($currentProduct, notes: $revisionNotes);
                }
            }
        }

        // Make sure the field layout is set correctly
        $this->fieldLayoutId = $this->getType()->fieldLayoutId;

        if ($this->enabled && !$this->postDate) {
            // Default the post date to the current date/time
            $this->postDate = new DateTime();
            // ...without the seconds
            $this->postDate->setTimestamp($this->postDate->getTimestamp() - ($this->postDate->getTimestamp() % 60));
        }

        return parent::beforeSave($isNew);
    }

    public function afterSave(bool $isNew): void
    {
        if (!$this->propagating) {
            if (!$isNew) {
                $record = EventRecord::findOne($this->id);

                if (!$record) {
                    throw new Exception('Invalid event id: ' . $this->id);
                }
            } else {
                $record = new EventRecord();
                $record->id = $this->id;
            }

            $record->capacity = $this->capacity;
            $record->postDate = $this->postDate;
            $record->expiryDate = $this->expiryDate;
            $record->typeId = $this->typeId;
            $record->ticketsCache = $this->ticketsCache;

            // We want to always have the same date as the element table, based on the logic for updating these in the element service i.e resaving
            $record->dateUpdated = $this->dateUpdated;
            $record->dateCreated = $this->dateCreated;

            $record->save(false);

            $this->id = $record->id;
        }

        parent::afterSave($isNew);
    }

    public function afterPropagate(bool $isNew): void
    {
        $this->getSessionManager()->maintainNestedElements($this, $isNew);
        $this->getTicketTypeManager()->maintainNestedElements($this, $isNew);
        
        parent::afterPropagate($isNew);

        // Save a new revision?
        if ($this->_shouldSaveRevision()) {
            Craft::$app->getRevisions()->createRevision($this, notes: $this->revisionNotes);
        }

        // Generate tickets based on the sessions and ticket types for this event
        if ($this->_shouldUpdateTickets()) {
            $this->updateTickets();
        }
    }

    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        $this->getSessionManager()->deleteNestedElements($this, $this->hardDelete);
        $this->getTicketTypeManager()->deleteNestedElements($this, $this->hardDelete);

        Db::update('{{%events_sessions}}', ['deletedWithEvent' => true], ['primaryOwnerId' => $this->id]);
        Db::update('{{%events_ticket_types}}', ['deletedWithEvent' => true], ['primaryOwnerId' => $this->id]);

        return true;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['capacity'], 'number', 'integerOnly' => true];
        $rules[] = [['updateTickets'], 'safe'];

        return $rules;
    }

    protected function route(): array|string|null
    {
        // Make sure that the entry is actually live
        if (!$this->previewing && $this->getStatus() != self::STATUS_LIVE) {
            return null;
        }
        
        // Make sure the event type is set to have URLs for this site
        $siteId = Craft::$app->getSites()->currentSite->id;
        $eventTypeSiteSettings = $this->getType()->getSiteSettings();

        if (!isset($eventTypeSiteSettings[$siteId]) || !$eventTypeSiteSettings[$siteId]->hasUrls) {
            return null;
        }

        return [
            'templates/render', [
                'template' => $eventTypeSiteSettings[$siteId]->template,
                'variables' => [
                    'event' => $this,
                    'product' => $this,
                ],
            ],
        ];
    }

    protected function crumbs(): array
    {
        $eventType = $this->getType();

        $eventTypes = Collection::make(Events::$plugin->getEventTypes()->getEditableEventTypes());
        
        /** @var Collection $eventTypeOptions */
        $eventTypeOptions = $eventTypes
            ->map(fn(EventType $t) => [
                'label' => Craft::t('site', $t->name),
                'url' => "events/events/$t->handle",
                'selected' => $t->id === $eventType->id,
            ]);

        return [
            [
                'label' => Craft::t('events', 'Events'),
                'url' => 'events/events',
            ],
            [
                'menu' => [
                    'label' => Craft::t('events', 'Select event type'),
                    'items' => $eventTypeOptions->all(),
                ],
            ],
        ];
    }

    protected function metaFieldsHtml(bool $static): string
    {
        $fields = [];
        $view = Craft::$app->getView();

        // Slug
        $fields[] = $this->slugFieldHtml($static);

        $fields[] = Cp::textFieldHtml([
            'status' => $this->getAttributeStatus('capacity'),
            'label' => Craft::t('events', 'Event Capacity'),
            'id' => 'capacity',
            'name' => 'capacity',
            'value' => $this->capacity,
            'placeholder' => $this->capacity ? '' : Craft::t('events', 'auto'),
            'class' => $this->capacity ? '' : 'disabled',
            'disabled' => !$this->capacity,
            'errors' => $this->getErrors('capacity'),
        ]);

        $isDeltaRegistrationActive = $view->getIsDeltaRegistrationActive();
        $view->setIsDeltaRegistrationActive(true);
        $view->registerDeltaName('postDate');
        $view->registerDeltaName('expiryDate');
        $view->setIsDeltaRegistrationActive($isDeltaRegistrationActive);

        // Post Date
        $fields[] = Cp::dateTimeFieldHtml([
            'status' => $this->getAttributeStatus('postDate'),
            'label' => Craft::t('app', 'Post Date'),
            'id' => 'postDate',
            'name' => 'postDate',
            'value' => $this->postDate,
            'errors' => $this->getErrors('postDate'),
            'disabled' => $static,
        ]);

        // Expiry Date
        $fields[] = Cp::dateTimeFieldHtml([
            'status' => $this->getAttributeStatus('expiryDate'),
            'label' => Craft::t('app', 'Expiry Date'),
            'id' => 'expiryDate',
            'name' => 'expiryDate',
            'value' => $this->expiryDate,
            'errors' => $this->getErrors('expiryDate'),
            'disabled' => $static,
        ]);

        $fields[] = parent::metaFieldsHtml($static);

        return implode("\n", $fields);
    }

    protected function uiLabel(): ?string
    {
        if (!isset($this->title) || trim($this->title) === '') {
            return Craft::t('app', 'Untitled {type}', [
                'type' => self::lowerDisplayName(),
            ]);
        }

        return null;
    }

    protected function attributeHtml(string $attribute): string
    {
        if ($attribute === 'type') {
            $type = $this->getType();

            return $type->name;
        }

        return parent::attributeHtml($attribute);
    }

    protected function cacheTags(): array
    {
        return [
            "eventType:$this->typeId",
        ];
    }

    protected function cpEditUrl(): ?string
    {
        $eventType = $this->getType();

        $path = sprintf('events/events/%s/%s', $eventType->handle, $this->getCanonicalId());

        // Ignore homepage/temp slugs
        if ($this->slug && !str_starts_with($this->slug, '__')) {
            $path .= sprintf('-%s', str_replace('/', '-', $this->slug));
        }

        return $path;
    }

    protected function cpRevisionsUrl(): ?string
    {
        return sprintf('%s/revisions', $this->cpEditUrl());
    }


    // Private Methods
    // =========================================================================

    private function _shouldSaveRevision(): bool
    {
        return ($this->id && !$this->propagating && !$this->resaving && !$this->getIsDraft() && !$this->getIsRevision() && $this->getType()->enableVersioning);
    }

    private function _shouldUpdateTickets(): bool
    {
        return ($this->id && !$this->propagating && !$this->resaving && !$this->getIsDraft() && !$this->getIsRevision() && $this->updateTickets);
    }

    private static function createSessionQuery(Event $event): SessionQuery
    {
        return Session::find()
            ->eventId($event->id)
            ->siteId($event->siteId)
            ->orderBy(['sortOrder' => SORT_ASC]);
    }

    private static function createTicketQuery(Event $event): TicketQuery
    {
        return Ticket::find()
            ->eventId($event->id)
            ->siteId($event->siteId);
    }

    private static function createTicketTypeQuery(Event $event): TicketTypeQuery
    {
        return TicketType::find()
            ->eventId($event->id)
            ->siteId($event->siteId)
            ->orderBy(['sortOrder' => SORT_ASC]);
    }

}
