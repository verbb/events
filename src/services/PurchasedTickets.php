<?php
namespace verbb\events\services;

use verbb\events\elements\PurchasedTicket;
use verbb\events\events\PurchasedTicketEvent;
use verbb\events\Events;
use verbb\events\records\PurchasedTicket as PurchasedTicketRecord;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\events\OrderStatusEvent;
use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\helpers\DateTimeHelper;

use yii\base\Component;
use yii\db\Expression;

use DateTime;

class PurchasedTickets extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_CHECK_IN = 'beforeCheckIn';
    public const EVENT_AFTER_CHECK_IN = 'afterCheckIn';
    public const EVENT_BEFORE_CHECK_OUT = 'beforeCheckOut';
    public const EVENT_AFTER_CHECK_OUT = 'afterCheckOut';


    // Public Methods
    // =========================================================================

    public function getPurchasedTicketById(int $id, $siteId = null): ?PurchasedTicket
    {
        return Craft::$app->getElements()->getElementById($id, PurchasedTicket::class, $siteId);
    }

    public function getPurchasedSeatsCountForEvent(int $eventId): int
    {
        if (!$eventId) {
            return 0;
        }

        $total = $this->_purchasedSeatsCountQuery(['eventId' => $eventId])->scalar();

        return (int)($total ?? 0);
    }

    public function getPurchasedSeatsCountForSession(int $sessionId): int
    {
        if (!$sessionId) {
            return 0;
        }

        $total = $this->_purchasedSeatsCountQuery(['sessionId' => $sessionId])->scalar();

        return (int)($total ?? 0);
    }

    public function getPurchasedTicketElementCountForTicket(int $ticketId, bool $includeTrashed = false): int
    {
        if (!$ticketId) {
            return 0;
        }

        if ($includeTrashed) {
            return PurchasedTicket::find()
                ->ticketId($ticketId)
                ->trashed(null)
                ->status(null)
                ->count();
        }

        return (int)$this->_activePurchasedTicketElementsQuery()
            ->where(['pt.ticketId' => $ticketId])
            ->count('*');
    }

    /**
     * @return array{purchasedTicketIds: int[], purged: int, skipped: int}
     */
    public function purgeTrashedPurchasedTickets(array $options = []): array
    {
        $dryRun = $options['dryRun'] ?? false;
        $eventId = $options['eventId'] ?? null;
        $olderThanDays = $options['olderThanDays'] ?? null;

        $query = PurchasedTicket::find()
            ->trashed(true)
            ->status(null)
            ->orderBy(['elements.dateDeleted' => SORT_ASC]);

        if ($eventId) {
            $query->eventId($eventId);
        }

        $threshold = null;

        if ($olderThanDays !== null && $olderThanDays > 0) {
            $threshold = DateTimeHelper::toDateTime("-{$olderThanDays} days");
        }

        $elementsService = Craft::$app->getElements();
        $purchasedTicketIds = [];
        $purged = 0;
        $skipped = 0;

        foreach ($query->all() as $purchasedTicket) {
            if ($threshold && (!$purchasedTicket->dateDeleted || $purchasedTicket->dateDeleted > $threshold)) {
                $skipped++;

                continue;
            }

            $purchasedTicketIds[] = $purchasedTicket->id;

            if ($dryRun) {
                continue;
            }

            if ($elementsService->deleteElement($purchasedTicket, true)) {
                $purged++;
            } else {
                $skipped++;
            }
        }

        return compact('purchasedTicketIds', 'purged', 'skipped');
    }

    public function trashPurchasedTicketsForOrder(Order $order): int
    {
        $purchasedTickets = PurchasedTicket::find()
            ->orderId($order->id)
            ->status(null)
            ->all();

        if ($purchasedTickets === []) {
            return 0;
        }

        $elementsService = Craft::$app->getElements();
        $trashed = 0;

        foreach ($purchasedTickets as $purchasedTicket) {
            if ($purchasedTicket->dateDeleted) {
                continue;
            }

            if ($elementsService->deleteElement($purchasedTicket)) {
                $trashed++;
            }
        }

        return $trashed;
    }

    public function onOrderStatusChange(OrderStatusEvent $event): void
    {
        $handles = Events::$plugin->getSettings()->releaseCapacityOrderStatusHandles;

        if ($handles === []) {
            return;
        }

        $orderStatus = $event->order->getOrderStatus();

        if (!$orderStatus || !in_array($orderStatus->handle, $handles, true)) {
            return;
        }

        $this->trashPurchasedTicketsForOrder($event->order);
    }

    public function checkInPurchasedTicket(PurchasedTicket $purchasedTicket): bool
    {
        $purchasedTicket->checkedIn = true;
        $purchasedTicket->checkedInDate = new DateTime();

        // Trigger a 'beforeCheckIn' event
        $event = new PurchasedTicketEvent([
            'purchasedTicket' => $purchasedTicket,
        ]);
        $this->trigger(self::EVENT_BEFORE_CHECK_IN, $event);

        if (!$event->isValid) {
            return false;
        }

        if (!Craft::$app->getElements()->saveElement($event->purchasedTicket)) {
            return false;
        }

        // Trigger a 'afterCheckIn' event
        $this->trigger(self::EVENT_AFTER_CHECK_IN, new PurchasedTicketEvent([
            'purchasedTicket' => $event->purchasedTicket,
        ]));

        return true;
    }

    public function checkOutPurchasedTicket(PurchasedTicket $purchasedTicket): bool
    {
        $purchasedTicket->checkedIn = false;
        $purchasedTicket->checkedInDate = null;

        // Trigger a 'beforeCheckOut' event
        $event = new PurchasedTicketEvent([
            'purchasedTicket' => $purchasedTicket,
        ]);
        $this->trigger(self::EVENT_BEFORE_CHECK_OUT, $event);

        if (!$event->isValid) {
            return false;
        }

        if (!Craft::$app->getElements()->saveElement($event->purchasedTicket)) {
            return false;
        }

        // Trigger a 'afterCheckOut' event
        $this->trigger(self::EVENT_AFTER_CHECK_OUT, new PurchasedTicketEvent([
            'purchasedTicket' => $event->purchasedTicket,
        ]));

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _activePurchasedTicketElementsQuery(): Query
    {
        return (new Query())
            ->from(['pt' => PurchasedTicketRecord::tableName()])
            ->innerJoin(['el' => CraftTable::ELEMENTS], [
                'and',
                '[[el.id]] = [[pt.id]]',
                ['el.dateDeleted' => null],
                ['el.enabled' => true],
            ]);
    }

    private function _purchasedSeatsCountQuery(array $conditions): Query
    {
        $query = $this->_activePurchasedTicketElementsQuery();

        foreach ($conditions as $column => $value) {
            $query->andWhere(["pt.$column" => $value]);
        }

        if (Craft::$app->getDb()->columnExists('events_ticket_types', 'seatsPerTicket')) {
            $query
                ->leftJoin(['tt' => '{{%events_ticket_types}}'], '[[pt.ticketTypeId]] = [[tt.id]]')
                ->select([new Expression('SUM(CASE WHEN COALESCE([[tt.seatsPerTicket]], 0) < 1 THEN 1 ELSE [[tt.seatsPerTicket]] END) AS total')]);
        } else {
            $query->select([new Expression('COUNT([[pt.id]]) AS total')]);
        }

        return $query;
    }
}
