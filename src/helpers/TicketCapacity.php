<?php
namespace verbb\events\helpers;

use verbb\events\Events;
use verbb\events\elements\PurchasedTicket;
use verbb\events\elements\Ticket;

use Craft;
use craft\db\Query;
use craft\db\Table;

use craft\commerce\elements\Order;
use craft\commerce\enums\LineItemType;
use craft\commerce\models\OrderNotice;
use craft\commerce\records\OrderNotice as OrderNoticeRecord;

use yii\db\Expression;

use RuntimeException;
use Throwable;

class TicketCapacity
{
    // Static Methods
    // =========================================================================

    public static function getOrderErrors(Order $order): array
    {
        if ($order->isCompleted) {
            return [];
        }

        $lines = self::_ticketLines($order);

        if (!$lines) {
            return [];
        }

        $state = self::_state(array_map(static fn($line) => $line->getPurchasable(), $lines));

        return self::_errors($lines, $state);
    }

    public static function allocateOrder(Order $order): bool
    {
        $lines = self::_ticketLines($order);

        if (!$lines) {
            return true;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            if (!$order->id || array_filter($lines, static fn($line) => !$line->id)) {
                throw new RuntimeException('Save the order and its line items before allocating tickets.');
            }

            $tickets = array_map(static fn($line) => $line->getPurchasable(), $lines);
            self::lockEvents(array_map(static fn($ticket) => $ticket->eventId, $tickets));
            $state = self::_state($tickets, true);
            $issued = [];

            // Released reservations still count as issued when completion is replayed.
            foreach ($state['reservations'] as $reservation) {
                if ((int)$reservation['orderId'] === $order->id) {
                    $id = (int)$reservation['lineItemId'];
                    $issued[$id] = ($issued[$id] ?? 0) + 1;
                }
            }

            if ($issued) {
                foreach ($lines as $line) {
                    if (($issued[$line->id] ?? 0) !== $line->qty) {
                        throw new RuntimeException('The order has a partial ticket allocation that requires review.');
                    }
                }

                $transaction->commit();
                self::_clearAllocationNotice($order);

                return true;
            }

            if (self::_errors($lines, $state)) {
                throw new RuntimeException('The remaining event or session capacity no longer covers this order.');
            }

            foreach ($lines as $line) {
                $ticket = $line->getPurchasable();
                $metadata = $state['metadata'][$ticket->id];

                for ($i = 0; $i < $line->qty; $i++) {
                    $purchase = new PurchasedTicket([
                        'eventId' => (int)$metadata['eventId'],
                        'sessionId' => (int)$metadata['sessionId'],
                        'ticketId' => $ticket->id,
                        'ticketTypeId' => (int)$metadata['typeId'],
                        'orderId' => $order->id,
                        'lineItemId' => $line->id,
                    ]);
                    $purchase->setFieldValues($ticket->getSerializedFieldValues());

                    foreach ($line->options as $option => $value) {
                        try {
                            $purchase->setFieldValue($option, $value);
                        } catch (Throwable) {
                            continue;
                        }
                    }

                    if (!Craft::$app->getElements()->saveElement($purchase, false)) {
                        throw new RuntimeException('A purchased ticket could not be saved.');
                    }
                }
            }

            $transaction->commit();
            self::_clearAllocationNotice($order);

            return true;
        } catch (Throwable $e) {
            if ($transaction->getIsActive()) {
                $transaction->rollBack();
            }

            // A gateway may already have accepted payment. Keep that record and flag fulfillment.
            self::_allocationNotice($order);
            Events::error('Ticket allocation failed for order ' . $order->id . ': ' . $e->getMessage());

            return false;
        }
    }

    public static function canRestore(PurchasedTicket $purchase, bool $lock = false): bool
    {
        $ticket = $purchase->getTicket();

        if (!$ticket) {
            return false;
        }

        $state = self::_state([$ticket], $lock);

        return self::_fits([$ticket->id => 1], $state);
    }

    public static function lockEvents(array $ids): void
    {
        $ids = array_unique(array_map('intval', $ids));
        sort($ids, SORT_NUMERIC);
        $db = Craft::$app->getDb();

        foreach ($ids as $id) {
            // An unchanged write also detects stale PostgreSQL repeatable-read snapshots.
            $db->createCommand()->update('{{%events_events}}', ['capacity' => new Expression('[[capacity]]')], ['id' => $id])->execute();
        }
    }

    private static function _ticketLines(Order $order): array
    {
        return array_values(array_filter($order->getLineItems(), static function($line): bool {
            // Custom line-item types were introduced in Commerce 5.1.
            if (enum_exists(LineItemType::class) && $line->type !== LineItemType::Purchasable) {
                return false;
            }

            return $line->getPurchasable() instanceof Ticket;
        }));
    }

    private static function _rows(Query $query, bool $lock): array
    {
        $command = $query->createCommand();

        if ($lock) {
            $params = $command->params;
            $command->setSql($command->getSql() . ' FOR UPDATE')->bindValues($params);
        }

        return $command->noCache()->queryAll();
    }

    private static function _state(array $tickets, bool $lock = false): array
    {
        $eventIds = array_unique(array_map(static fn($ticket) => $ticket->eventId, $tickets));
        $rows = self::_rows((new Query())
            ->select(['t.id', 't.eventId', 't.sessionId', 't.typeId', 'ticketCapacity' => 'tt.capacity', 'tt.seatsPerTicket', 'sessionCapacity' => 's.capacity', 'eventCapacity' => 'e.capacity'])
            ->from(['t' => '{{%events_tickets}}'])
            ->innerJoin(['tt' => '{{%events_ticket_types}}'], '[[tt.id]] = [[t.typeId]]')
            ->innerJoin(['s' => '{{%events_sessions}}'], '[[s.id]] = [[t.sessionId]]')
            ->innerJoin(['e' => '{{%events_events}}'], '[[e.id]] = [[t.eventId]]')
            ->where(['t.id' => array_map(static fn($ticket) => $ticket->id, $tickets), 't.eventId' => $eventIds])
            ->orderBy(['t.id' => SORT_ASC]), $lock);
        $metadata = [];

        foreach ($rows as $row) {
            $metadata[(int)$row['id']] = $row;
        }

        $reservations = self::_rows((new Query())
            ->select(['pt.*', 'el.enabled', 'el.dateDeleted'])
            ->from(['pt' => '{{%events_purchased_tickets}}'])
            ->innerJoin(['el' => Table::ELEMENTS], '[[el.id]] = [[pt.id]]')
            ->where(['pt.eventId' => $eventIds])
            ->orderBy(['pt.id' => SORT_ASC]), $lock);
        $weights = [];

        foreach (self::_rows((new Query())->select(['id', 'seatsPerTicket'])->from('{{%events_ticket_types}}')->where(['id' => array_column($reservations, 'ticketTypeId')])->orderBy(['id' => SORT_ASC]), $lock) as $row) {
            $weights[(int)$row['id']] = max(1, (int)$row['seatsPerTicket']);
        }

        $used = ['eventId' => [], 'sessionId' => [], 'ticketId' => []];

        foreach ($reservations as $reservation) {
            if (!$reservation['enabled'] || $reservation['dateDeleted'] || $reservation['reservationStatus'] !== PurchasedTicket::RESERVATION_STATUS_ACTIVE) {
                continue;
            }

            $seats = $weights[(int)$reservation['ticketTypeId']] ?? 1;

            foreach ($used as $key => $counts) {
                $id = (int)$reservation[$key];
                $used[$key][$id] = ($counts[$id] ?? 0) + $seats;
            }
        }

        return compact('metadata', 'reservations', 'used');
    }

    private static function _errors(array $lines, array $state): array
    {
        $quantities = [];

        foreach ($lines as $line) {
            $id = $line->purchasableId;
            $quantities[$id] = ($quantities[$id] ?? 0) + $line->qty;
        }

        return self::_fits($quantities, $state) ? [] : [Craft::t('events', 'There are not enough remaining seats for all tickets in this order. Please update your cart.')];
    }

    private static function _fits(array $quantities, array $state): bool
    {
        $demand = ['eventId' => [], 'sessionId' => [], 'ticketId' => []];
        $limits = $demand;

        foreach ($quantities as $id => $quantity) {
            $row = $state['metadata'][$id] ?? null;

            if (!$row || $quantity < 0) {
                return false;
            }

            $seats = $quantity * max(1, (int)$row['seatsPerTicket']);

            foreach (['eventId' => 'eventCapacity', 'sessionId' => 'sessionCapacity', 'ticketId' => 'ticketCapacity'] as $key => $limit) {
                $poolId = (int)($key === 'ticketId' ? $id : $row[$key]);
                $demand[$key][$poolId] = ($demand[$key][$poolId] ?? 0) + $seats;
                $limits[$key][$poolId] = $row[$limit];
            }
        }

        foreach ($demand as $key => $pools) {
            foreach ($pools as $id => $seats) {
                $limit = $limits[$key][$id];

                if ($limit !== null && $limit !== '' && $seats > max(0, (int)$limit - ($state['used'][$key][$id] ?? 0))) {
                    return false;
                }
            }
        }

        return true;
    }

    private static function _allocationNotice(Order $order): void
    {
        $type = 'eventsTicketAllocation';
        $message = Craft::t('events', 'Event tickets could not be allocated for this order. Review ticket availability and arrange fulfillment or a refund before confirming attendance.');
        $record = $order->id ? OrderNoticeRecord::findOne(['orderId' => $order->id, 'type' => $type]) : null;
        $record ??= new OrderNoticeRecord();
        $record->setAttributes(['orderId' => $order->id, 'type' => $type, 'attribute' => 'lineItems', 'message' => $message], false);

        // Admin-only notices were added in Commerce 5.7; older versions use a normal notice.
        if ($record->hasAttribute('noticeType')) {
            $record->setAttribute('noticeType', 'admin');
        }

        if ($order->id) {
            $record->save(false);
        }

        $notice = new OrderNotice(['id' => $record->id, 'type' => $type, 'attribute' => 'lineItems', 'message' => $message]);

        if (method_exists($notice, 'setNoticeType')) {
            $notice->setNoticeType('admin');
        }

        $order->addNotice($notice);
    }

    private static function _clearAllocationNotice(Order $order): void
    {
        OrderNoticeRecord::deleteAll(['orderId' => $order->id, 'type' => 'eventsTicketAllocation']);
        $notice = new OrderNotice();

        if (method_exists($notice, 'setNoticeType')) {
            $notice->setNoticeType('admin');
            $order->clearNotices('eventsTicketAllocation', null, $notice->getNoticeType());
        } else {
            $order->clearNotices('eventsTicketAllocation');
        }
    }
}
