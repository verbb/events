<?php
namespace verbb\events\services;

use verbb\events\elements\PurchasedTicket;
use verbb\events\elements\Ticket;
use verbb\events\events\PurchasedTicketEvent;
use verbb\events\Events;
use verbb\events\records\PurchasedTicket as PurchasedTicketRecord;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\events\OrderStatusEvent;
use craft\commerce\events\RefundTransactionEvent;
use craft\commerce\models\LineItem;
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
    public const EVENT_BEFORE_CANCEL = 'beforeCancel';
    public const EVENT_AFTER_CANCEL = 'afterCancel';
    public const EVENT_BEFORE_RESTORE = 'beforeRestore';
    public const EVENT_AFTER_RESTORE = 'afterRestore';


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

    public function getCancelledSeatsCountForEvent(int $eventId): int
    {
        if (!$eventId) {
            return 0;
        }

        $total = $this->_cancelledSeatsCountQuery(['eventId' => $eventId])->scalar();

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

    public function getPurchasedTicketElementCountForTicket(?int $ticketId, bool $includeInactive = false): int
    {
        if (!$ticketId) {
            return 0;
        }

        if ($includeInactive) {
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

    public function cancelPurchasedTicket(PurchasedTicket $purchasedTicket, ?string $reason = null): bool
    {
        if (!$purchasedTicket->getIsActive()) {
            return false;
        }

        if ($purchasedTicket->checkedIn) {
            $this->checkOutPurchasedTicket($purchasedTicket);
        }

        $event = new PurchasedTicketEvent([
            'purchasedTicket' => $purchasedTicket,
            'reason' => $reason,
        ]);
        $this->trigger(self::EVENT_BEFORE_CANCEL, $event);

        if (!$event->isValid) {
            return false;
        }

        $purchasedTicket = $event->purchasedTicket;
        $purchasedTicket->reservationStatus = PurchasedTicket::RESERVATION_STATUS_CANCELLED;
        $purchasedTicket->cancelledAt = new DateTime();
        $purchasedTicket->cancelledReason = $reason ?: null;
        $purchasedTicket->cancelledById = Craft::$app->getUser()->getId();

        if (!Craft::$app->getElements()->saveElement($purchasedTicket)) {
            return false;
        }

        $this->trigger(self::EVENT_AFTER_CANCEL, new PurchasedTicketEvent([
            'purchasedTicket' => $purchasedTicket,
            'reason' => $reason,
        ]));

        return true;
    }

    public function restorePurchasedTicket(PurchasedTicket $purchasedTicket): bool
    {
        if (!$purchasedTicket->getIsCancelled() || !$this->canRestorePurchasedTicket($purchasedTicket)) {
            return false;
        }

        $event = new PurchasedTicketEvent([
            'purchasedTicket' => $purchasedTicket,
        ]);
        $this->trigger(self::EVENT_BEFORE_RESTORE, $event);

        if (!$event->isValid) {
            return false;
        }

        $purchasedTicket = $event->purchasedTicket;
        $purchasedTicket->reservationStatus = PurchasedTicket::RESERVATION_STATUS_ACTIVE;
        $purchasedTicket->cancelledAt = null;
        $purchasedTicket->cancelledReason = null;
        $purchasedTicket->cancelledById = null;

        if (!Craft::$app->getElements()->saveElement($purchasedTicket)) {
            return false;
        }

        $this->trigger(self::EVENT_AFTER_RESTORE, new PurchasedTicketEvent([
            'purchasedTicket' => $purchasedTicket,
        ]));

        return true;
    }

    public function canRestorePurchasedTicket(PurchasedTicket $purchasedTicket): bool
    {
        if (!$purchasedTicket->getIsCancelled()) {
            return false;
        }

        if (Events::$plugin->getSettings()->allowRestoreWhenOrderCancelled) {
            return true;
        }

        $order = $purchasedTicket->getOrder();
        $orderStatus = $order?->getOrderStatus();
        $handles = Events::$plugin->getSettings()->releaseCapacityOrderStatusHandles;

        if ($orderStatus && in_array($orderStatus->handle, $handles, true)) {
            return false;
        }

        return true;
    }

    public function cancelForOrder(Order $order, ?string $reason = null): int
    {
        $purchasedTickets = PurchasedTicket::find()
            ->orderId($order->id)
            ->reservationStatus(PurchasedTicket::RESERVATION_STATUS_ACTIVE)
            ->status(null)
            ->all();

        $cancelled = 0;

        foreach ($purchasedTickets as $purchasedTicket) {
            if ($this->cancelPurchasedTicket($purchasedTicket, $reason)) {
                $cancelled++;
            }
        }

        return $cancelled;
    }

    public function cancelForLineItem(LineItem $lineItem, ?int $qty = null, ?string $reason = null): int
    {
        $query = PurchasedTicket::find()
            ->lineItemId($lineItem->id)
            ->reservationStatus(PurchasedTicket::RESERVATION_STATUS_ACTIVE)
            ->status(null)
            ->orderBy(['id' => SORT_ASC]);

        if ($qty !== null) {
            $query->limit($qty);
        }

        $cancelled = 0;

        foreach ($query->all() as $purchasedTicket) {
            if ($this->cancelPurchasedTicket($purchasedTicket, $reason)) {
                $cancelled++;
            }
        }

        return $cancelled;
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

        $this->cancelForOrder($event->order, Craft::t('events', 'Order status changed to {status}.', [
            'status' => $orderStatus->name,
        ]));
    }

    public function onAfterRefundTransaction(RefundTransactionEvent $event): void
    {
        if (!Events::$plugin->getSettings()->cancelPurchasedTicketsOnRefund) {
            return;
        }

        $order = $event->transaction->getOrder();

        if (!$order) {
            return;
        }

        $ticketLineItems = array_values(array_filter(
            $order->getLineItems(),
            fn(LineItem $lineItem) => $lineItem->purchasable instanceof Ticket,
        ));

        if ($ticketLineItems === []) {
            return;
        }

        $reason = Craft::t('events', 'Order refunded.');

        if (count($ticketLineItems) === 1) {
            $lineItem = $ticketLineItems[0];
            $unitPrice = $lineItem->getSalePrice();

            if ($unitPrice > 0) {
                $qty = max(1, (int)round($event->amount / $unitPrice));
                $this->cancelForLineItem($lineItem, $qty, $reason);

                return;
            }
        }

        if ($event->amount >= $event->transaction->paymentAmount) {
            $this->cancelForOrder($order, $reason);
        }
    }

    public function checkInPurchasedTicket(PurchasedTicket $purchasedTicket): bool
    {
        if (!$purchasedTicket->getIsActive()) {
            return false;
        }

        $purchasedTicket->checkedIn = true;
        $purchasedTicket->checkedInDate = new DateTime();

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

        $this->trigger(self::EVENT_AFTER_CHECK_IN, new PurchasedTicketEvent([
            'purchasedTicket' => $event->purchasedTicket,
        ]));

        return true;
    }

    public function checkOutPurchasedTicket(PurchasedTicket $purchasedTicket): bool
    {
        $purchasedTicket->checkedIn = false;
        $purchasedTicket->checkedInDate = null;

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

        $this->trigger(self::EVENT_AFTER_CHECK_OUT, new PurchasedTicketEvent([
            'purchasedTicket' => $event->purchasedTicket,
        ]));

        return true;
    }


    // Private Methods
    // =========================================================================

    private function _activePurchasedTicketElementsQuery(): Query
    {
        $query = (new Query())
            ->from(['pt' => PurchasedTicketRecord::tableName()])
            ->innerJoin(['el' => CraftTable::ELEMENTS], [
                'and',
                '[[el.id]] = [[pt.id]]',
                ['el.dateDeleted' => null],
                ['el.enabled' => true],
            ]);

        if (Craft::$app->getDb()->columnExists(PurchasedTicketRecord::tableName(), 'reservationStatus')) {
            $query->andWhere(['pt.reservationStatus' => PurchasedTicket::RESERVATION_STATUS_ACTIVE]);
        }

        return $query;
    }

    private function _purchasedSeatsCountQuery(array $conditions): Query
    {
        return $this->_seatsCountQuery($this->_activePurchasedTicketElementsQuery(), $conditions);
    }

    private function _cancelledSeatsCountQuery(array $conditions): Query
    {
        $query = (new Query())
            ->from(['pt' => PurchasedTicketRecord::tableName()])
            ->innerJoin(['el' => CraftTable::ELEMENTS], [
                'and',
                '[[el.id]] = [[pt.id]]',
                ['el.dateDeleted' => null],
            ]);

        if (Craft::$app->getDb()->columnExists(PurchasedTicketRecord::tableName(), 'reservationStatus')) {
            $query->andWhere(['pt.reservationStatus' => PurchasedTicket::RESERVATION_STATUS_CANCELLED]);
        } else {
            return (new Query())->select([new Expression('0 AS total')]);
        }

        return $this->_seatsCountQuery($query, $conditions);
    }

    private function _seatsCountQuery(Query $query, array $conditions): Query
    {
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
