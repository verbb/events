<?php
namespace verbb\events\elements;

use verbb\events\Events;
use verbb\events\elements\db\PurchasedTicketQuery;
use verbb\events\records\PurchasedTicket as PurchasedTicketRecord;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\elements\User;
use craft\elements\actions\Delete;
use craft\elements\actions\Duplicate;
use craft\helpers\Cp;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;

use craft\commerce\Plugin as Commerce;
use craft\commerce\models\LineItem;
use craft\commerce\elements\Order;

use yii\base\Exception;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

use DateTime;

class PurchasedTicket extends Element
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('events', 'Purchased Ticket');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('events', 'Purchased Tickets');
    }

    public static function refHandle(): ?string
    {
        return 'purchasedTicket';
    }

    public static function trackChanges(): bool
    {
        return true;
    }

    public static function hasContent(): bool
    {
        return true;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function find(): PurchasedTicketQuery
    {
        return new PurchasedTicketQuery(static::class);
    }

    public static function gqlTypeNameByContext(mixed $context): string
    {
        return $context->handle . '_PurchasedTicket';
    }

    public static function gqlScopesByContext(mixed $context): array
    {
        return ['eventsEventTypes.' . $context->uid];
    }

    protected static function defineSources(string $context = null): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('events', 'All purchased tickets'),
            ],
        ];

        foreach (Events::$plugin->getEventTypes()->getAllEventTypes() as $eventType) {
            $sources[] = [
                'heading' => Craft::t('events', '{name} Events', [
                    'name' => $eventType->name,
                ]),
            ];

            foreach (Event::find()->typeId($eventType->id)->all() as $event) {
                $key = "eventType:$eventType->uid:event:$event->id";

                $sources[$key] = [
                    'key' => $key,
                    'label' => $event->title,
                    'criteria' => [
                        'eventId' => $event->id,
                    ],
                ];

                foreach (Session::find()->ownerId($event->id)->all() as $session) {
                    $sources[$key]['nested'][] = [
                        'key' => "$key:session:$session->id",
                        'label' => $session->title,
                        'criteria' => [
                            'sessionId' => $session->id,
                        ],
                    ];
                }
            }
        }

        return $sources;
    }

    protected static function includeSetStatusAction(): bool
    {
        return true;
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['event', 'ticket', 'order'];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'checkedIn' => Craft::t('events', 'Checked In?'),
            'checkedInDate' => Craft::t('events', 'Checked In Date'),
            'dateCreated' => Craft::t('events', 'Date Created'),
            'dateUpdated' => Craft::t('events', 'Date Updated'),
        ];
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'eventId' => Craft::t('events', 'Event'),
            'sessionId' => Craft::t('events', 'Session'),
            'ticketId' => Craft::t('events', 'Ticket'),
            'ticketTypeId' => Craft::t('events', 'Ticket Type'),
            'orderId' => Craft::t('events', 'Order'),
            'customer' => Craft::t('events', 'Customer'),
            'customerFirstName' => Craft::t('events', 'Customer First Name'),
            'customerLastName' => Craft::t('events', 'Customer Last Name'),
            'customerFullName' => Craft::t('events', 'Customer Full Name'),
            'checkedIn' => Craft::t('events', 'Checked In?'),
            'checkedInDate' => Craft::t('events', 'Checked In Date'),
            'dateCreated' => Craft::t('events', 'Date Created'),
            'dateUpdated' => Craft::t('events', 'Date Updated'),
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'checkedIn',
            'dateCreated',
        ];
    }

    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Craft::t('events', 'Are you sure you want to delete the selected purchased tickets?'),
            'successMessage' => Craft::t('events', 'Purchased tickets deleted.'),
        ]);

        $actions[] = [
            'type' => Duplicate::class,
        ];

        return $actions;
    }


    // Properties
    // =========================================================================

    public ?bool $checkedIn = null;
    public ?DateTime $checkedInDate = null;
    public ?int $eventId = null;
    public ?int $sessionId = null;
    public ?int $ticketId = null;
    public ?int $ticketTypeId = null;
    public ?int $lineItemId = null;
    public ?int $orderId = null;
    // public ?string $ticketSku = null;

    private ?Event $_event = null;
    private ?Session $_session = null;
    private ?Ticket $_ticket = null;
    private ?TicketType $_ticketType = null;
    private ?User $_customer = null;
    private ?LineItem $_lineItem = null;
    private ?Order $_order = null;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        // Title is dynamic
        if ($eventType = $this->getEvent()?->getType()) {
            try {
                // Title is dynamic
                $this->title = Craft::$app->getView()->renderObjectTemplate($eventType->purchasedTicketTitleFormat, $this);
            } catch (Throwable $e) {
            }
        }

        parent::init();
    }

    public function canView(User $user): bool
    {
        return $user->can('events-managePurchasedTickets');
    }

    public function canSave(User $user): bool
    {
        return $user->can('events-managePurchasedTickets');
    }

    public function canDuplicate(User $user): bool
    {
        return $user->can('events-managePurchasedTickets');
    }

    public function canDelete(User $user): bool
    {
        return $user->can('events-managePurchasedTickets');
    }

    public function canDeleteForSite(User $user): bool
    {
        return $this->canDelete($user);
    }

    public function createAnother(): ?ElementInterface
    {
        return null;
    }

    public function getFieldLayout(): ?FieldLayout
    {
        if ($ticketType = $this->getTicketType()) {
            if ($ticketTypeFieldLayout = $ticketType->getFieldLayout()) {
                // Return a new field layout for just the custom fields
                $fields = $ticketTypeFieldLayout->getCustomFieldElements();

                $fieldLayout = new FieldLayout([
                    'type' => self::class,
                ]);

                // Populate the field layout
                $tab1 = new FieldLayoutTab(['name' => 'Content']);
                $tab1->setLayout($fieldLayout);
                $tab1->setElements($fields);
                $fieldLayout->setTabs([$tab1]);

                return $fieldLayout;
            }
        }

        return null;
    }

    public function getEvent(): ?Event
    {
        if ($this->_event) {
            return $this->_event;
        }

        if ($this->eventId) {
            return $this->_event = Event::find()->id($this->eventId)->one();
        }

        return null;
    }

    public function getSession(): ?Session
    {
        if ($this->_session) {
            return $this->_session;
        }

        if ($this->sessionId) {
            return $this->_session = Session::find()->id($this->sessionId)->one();
        }

        return null;
    }

    public function getTicket(): ?Ticket
    {
        if ($this->_ticket) {
            return $this->_ticket;
        }

        if ($this->ticketId) {
            return $this->_ticket = Ticket::find()->id($this->ticketId)->one();
        }

        return null;
    }

    public function getTicketType(): ?TicketType
    {
        if ($this->_ticketType) {
            return $this->_ticketType;
        }

        if ($this->ticketTypeId) {
            return $this->_ticketType = TicketType::find()->id($this->ticketTypeId)->one();
        }

        return null;
    }

    public function getOrder(): ?Order
    {
        if ($this->_order) {
            return $this->_order;
        }

        if ($this->orderId) {
            return $this->_order = Commerce::getInstance()->getOrders()->getOrderById($this->orderId);
        }

        return null;
    }

    public function getLineItem(): ?LineItem
    {
        if ($this->_lineItem) {
            return $this->_lineItem;
        }

        if ($this->lineItemId) {
            return $this->_lineItem = Commerce::getInstance()->getLineItems()->getLineItemById($this->lineItemId);
        }

        return null;
    }

    public function getCustomer(): ?User
    {
        if ($this->_customer) {
            return $this->_customer;
        }

        if ($order = $this->getOrder()) {
            return $this->_customer = $order->getCustomer();
        }

        return null;
    }

    public function getEventType()
    {
        $event = $this->getEvent();

        return $event?->getEventType();
    }

    public function getCheckInUrl(): string
    {
        return UrlHelper::actionUrl('events/tickets/check-in', ['uid' => $this->uid]);
    }

    public function getQrCode(): string
    {
        $url = $this->getCheckInUrl();

        $qrCode = QrCode::create($url)
            ->setEncoding(new Encoding('UTF-8'))
            ->setSize(300)
            ->setMargin(0)
            ->setErrorCorrectionLevel(new ErrorCorrectionLevelMedium())
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return $result->getDataUri();
    }

    public function getGqlTypeName(): string
    {
        $event = $this->getEvent();

        if (!$event) {
            return 'PurchasedTicket';
        }

        try {
            $eventType = $event->getType();
        } catch (Exception) {
            return 'PurchasedTicket';
        }

        return static::gqlTypeNameByContext($eventType);
    }

    public function afterSave(bool $isNew): void
    {
        if (!$isNew) {
            $purchasedTicketRecord = PurchasedTicketRecord::findOne($this->id);

            if (!$purchasedTicketRecord) {
                throw new Exception('Invalid purchased ticket id: ' . $this->id);
            }
        } else {
            $purchasedTicketRecord = new PurchasedTicketRecord();
            $purchasedTicketRecord->id = $this->id;
        }

        $purchasedTicketRecord->eventId = $this->eventId;
        $purchasedTicketRecord->sessionId = $this->sessionId;
        $purchasedTicketRecord->ticketId = $this->ticketId;
        $purchasedTicketRecord->ticketTypeId = $this->ticketTypeId;
        $purchasedTicketRecord->orderId = $this->orderId;
        $purchasedTicketRecord->lineItemId = $this->lineItemId;
        $purchasedTicketRecord->checkedIn = $this->checkedIn;
        $purchasedTicketRecord->checkedInDate = $this->checkedInDate;

        $purchasedTicketRecord->save(false);

        parent::afterSave($isNew);
    }


    // Protected Methods
    // =========================================================================

    protected function attributeHtml(string $attribute): string
    {
        if ($attribute === 'eventId') {
            $event = $this->getEvent();

            return $event ? Cp::elementChipHtml($event) : '';
        }

        if ($attribute === 'sessionId') {
            $session = $this->getSession();

            return $session ? Cp::elementChipHtml($session) : '';
        }

        if ($attribute === 'ticketId') {
            $ticket = $this->getTicket();

            return $ticket ? Cp::elementChipHtml($ticket) : '';
        }

        if ($attribute === 'ticketTypeId') {
            $ticketType = $this->getTicketType();

            return $ticketType ? Cp::elementChipHtml($ticketType) : '';
        }

        if ($attribute === 'orderId') {
            $order = $this->getOrder();

            return $order ? Cp::elementChipHtml($order) : '';
        }

        if ($attribute === 'customer') {
            if (($customer = $this->getCustomer())) {
                return (string)$customer->email;
            }

            if ($order = $this->getOrder()) {
                return $order->email;
            }

            return '';
        }

        if ($attribute === 'customerFirstName') {
            if (($customer = $this->getCustomer())) {
                return (string)$customer->firstName;
            }

            return Craft::t('events', '[Guest]');
        }

        if ($attribute === 'customerLastName') {
            if (($customer = $this->getCustomer())) {
                return (string)$customer->lastName;
            }

            return Craft::t('events', '[Guest]');
        }

        if ($attribute === 'customerFullName') {
            if (($customer = $this->getCustomer())) {
                return (string)$customer->fullName;
            }

            return Craft::t('events', '[Guest]');
        }

        if ($attribute === 'checkedIn') {
            return '<span class="status ' . ($this->checkedIn ? 'live' : 'disabled') . '"></span>';
        }

        return parent::attributeHtml($attribute);
    }

    protected function cpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('events/purchased-tickets/' . $this->id);
    }
}
