<?php
namespace verbb\events\elements;

use verbb\events\Events;
use verbb\events\elements\db\PurchasedTicketQuery;
use verbb\events\records\PurchasedTicketRecord;
use verbb\events\elements\actions\Checkin;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\actions\Delete;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\UrlHelper;

use craft\commerce\Plugin as Commerce;

use yii\base\Exception;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\ErrorCorrectionLevel;

class PurchasedTicket extends Element
{
    // Static
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('events', 'Purchased Ticket');
	}
	
	public static function refHandle()
    {
        return 'purchasedTicket';
    }
	
	public static function hasContent(): bool
    {
        return true;
	}

    public static function find(): ElementQueryInterface
    {
        return new PurchasedTicketQuery(static::class);
    }

    protected static function defineSources(string $context = null): array
    {
        $sources = [[
            'key' => '*',
            'label' => Craft::t('events', 'All purchased tickets'),
        ]];

        $eventElements = (new Query())
            ->select(['elements.id', 'purchasedtickets.eventId', 'content.title', 'eventtypes.name as eventTypeName'])
            ->from(['{{%elements}} elements'])
            ->innerJoin('{{%content}} content', '[[content.elementId]] = [[elements.id]]')
			->innerJoin('{{%events_purchasedtickets}} purchasedtickets', '[[purchasedtickets.eventId]] = [[elements.id]]')
			->innerJoin('{{%events_events}} events', '[[purchasedtickets.eventId]] = [[events.id]]')
			->innerJoin('{{%events_eventtypes}} eventtypes', '[[events.typeId]] = [[eventtypes.id]]')
            ->groupBy(['typeId', 'eventId', 'eventTypeName', 'title', 'elements.id'])
            ->all();

		$type = null;

        foreach ($eventElements as $element) {
			if ($element['eventTypeName'] != $type) {
				$type = $element['eventTypeName'];
				$sources[] = ['heading' => Craft::t('events', '{name} Events', ['name' => $element['eventTypeName']])];
			}

            $sources['elements:' . $element['eventId']] = [
                'key' => 'elements:' . $element['eventId'],
                'label' => $element['title'],
                'criteria' => [
                    'eventId' => $element['eventId'],
                ],
            ];
        }

        return $sources;
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['ticketSku', 'event', 'ticket', 'order'];
    }


    // Element index methods
    // -------------------------------------------------------------------------

    protected static function defineSortOptions(): array
    {
        return [
            'ticketSku' => Craft::t('events', 'Ticket SKU'),
            'checkedIn' => Craft::t('events', 'Checked In?'),
            'checkedInDate' => Craft::t('events', 'Checked In Date'),
            'dateCreated' => Craft::t('events', 'Date Created'),
            'dateUpdated' => Craft::t('events', 'Date Updated'),
        ];
    }

    protected static function defineTableAttributes(): array
    {
        $attributes = [
            'ticketSku' => Craft::t('events', 'Ticket SKU'),
            'eventId' => Craft::t('events', 'Event'),
            'ticketId' => Craft::t('events', 'Ticket'),
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

        // Include ticket custom fields
        foreach (Craft::$app->elementIndexes->getAvailableTableFields(Ticket::class) as $field) {
            $attributes['field:' . $field->id] = ['label' => Craft::t('site', $field->name)];
        }

        return $attributes;
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'ticketSku',
            'eventId',
            'ticketId',
            'orderId',
            'checkedIn',
            'dateCreated',
        ];
    }

    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'eventId': {
                $event = $this->getEvent();

                if ($event) {
                    return "<a href='" . $event->cpEditUrl . "'>" . $event->title . "</a>";
                } else {
                    return Craft::t('events', '[Deleted event]');
                }
            }
            case 'ticketId': {
                $ticket = $this->getTicket();

                if ($ticket) {
                    return "<a href='" . $ticket->cpEditUrl . "'>" . $ticket->title . "</a>";
                } else {
                    return Craft::t('events', '[Deleted ticket]');
                }
            }
            case 'orderId': {
                $order = $this->getOrder();

                if ($order) {
                    return "<a href='" . $order->cpEditUrl . "'>" . $order->reference . "</a>";
                } else {
                    return Craft::t('events', '[Deleted order]');
                }
            }
            case 'customer': {
                if ($customer = $this->getCustomer()) {
                    return (string)$customer;
                }

                return '';
            }
            case 'customerFirstName': {
                if ($customer = $this->getCustomer()) {
                    if ($customer->user) {
                        return $customer->user->firstName;
                    }
                }

                return Craft::t('events', '[Guest]');
            }
            case 'customerLastName': {
                if ($customer = $this->getCustomer()) {
                    if ($customer->user) {
                        return $customer->user->lastName;
                    }
                }

                return Craft::t('events', '[Guest]');
            }
            case 'customerFullName': {
                if ($customer = $this->getCustomer()) {
                    if ($customer->user) {
                        return $customer->user->fullName;
                    }
                }

                return Craft::t('events', '[Guest]');
            }
            case 'checkedIn': {
                return '<span class="status ' . ($this->checkedIn ? 'live' : 'disabled') . '"></span>';
            }
            default: {
                return parent::tableAttributeHtml($attribute);
            }
        }
    }

    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Craft::t('events', 'Are you sure you want to delete the selected purchased tickets?'),
            'successMessage' => Craft::t('events', 'Purchased tickets deleted.'),
        ]);

        return $actions;
    }


    // Properties
    // =========================================================================

    public $eventId;
    public $ticketId;
    public $orderId;
    public $lineItemId;
    public $ticketSku;
    public $checkedIn;
    public $checkedInDate;

    private $_event;
    private $_ticket;
    private $_order;
    private $_lineItem;
    private $_customer;


    // Public Methods
    // =========================================================================

    public function __toString()
    {
        return $this->ticketSku ?? '';
    }

    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'checkedInDate';

        return $attributes;
    }

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('events/purchased-tickets/' . $this->id);
	}
	
	public function getFieldLayout()
    {   
        if ($ticket = $this->getTicket()) {
            return $ticket->getFieldLayout();
        }

        return null;
	}

    public function getEvent()
    {
        if ($this->_event) {
            return $this->_event;
        }

        if ($this->eventId) {
            return $this->_event = Events::$plugin->getEvents()->getEventById($this->eventId);
        }

        return null;
    }

    public function getTicket()
    {
        if ($this->_ticket) {
            return $this->_ticket;
        }

        if ($this->ticketId) {
            return $this->_ticket = Events::$plugin->getTickets()->getTicketById($this->ticketId);
        }

        return null;
    }

    public function getOrder()
    {
        if ($this->_order) {
            return $this->_order;
        }

        if ($this->orderId) {
            return $this->_order = Commerce::getInstance()->getOrders()->getOrderById($this->orderId);
        }

        return null;
    }

    public function getLineItem()
    {
        if ($this->_lineItem) {
            return $this->_lineItem;
        }

        if ($this->lineItemId) {
            return $this->_lineItem = Commerce::getInstance()->getLineItems()->getLineItemById($this->lineItemId);
        }

        return null;
    }

    public function getCustomer()
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

        if ($event) {
            return $event->getEventType();
        }

        return null;
    }

    public function getTicketType()
    {
        $ticket = $this->getTicket();

        if ($ticket) {
            return $ticket->getTicketType();
        }

        return null;
    }

    public function getEventName()
    {
        return $this->getEvent()->getTitle();
    }

    public function getTicketName()
    {
        return $this->getTicket()->getName();
    }

    public function getQrCode()
    {
        $url = UrlHelper::actionUrl('events/ticket/checkin', ['sku' => $this->ticketSku]);

        $qrCode = new QrCode();

        $qrCode
            ->setText($url)
            ->setSize(300)
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH)
            ->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0])
            ->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);

        return $qrCode->writeDataUri();
    }


    // Events
    // -------------------------------------------------------------------------

    public function afterSave(bool $isNew)
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
        $purchasedTicketRecord->ticketId = $this->ticketId;
        $purchasedTicketRecord->orderId = $this->orderId;
        $purchasedTicketRecord->lineItemId = $this->lineItemId;
        $purchasedTicketRecord->ticketSku = $this->ticketSku;
        $purchasedTicketRecord->checkedIn = $this->checkedIn;
        $purchasedTicketRecord->checkedInDate = $this->checkedInDate;

        $purchasedTicketRecord->save(false);

        return parent::afterSave($isNew);
    }
}
