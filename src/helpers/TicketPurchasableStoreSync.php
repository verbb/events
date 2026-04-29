<?php
namespace verbb\events\helpers;

use Craft;
use craft\db\Query;
use craft\commerce\Plugin as Commerce;
use craft\commerce\records\PurchasableStore as PurchasableStoreRecord;
use verbb\events\elements\Ticket;

use Throwable;

final class TicketPurchasableStoreSync
{
    // Static Methods
    // =========================================================================

    public static function syncTicketToAllStores(Ticket $ticket): void
    {
        if (!$ticket->id || $ticket->getIsDraft() || $ticket->getIsRevision()) {
            return;
        }

        if (!Craft::$app->getDb()->tableExists('{{%commerce_purchasables_stores}}')) {
            return;
        }

        $stores = Commerce::getInstance()->getStores()->getAllStores();

        if ($stores->isEmpty()) {
            return;
        }

        $template = PurchasableStoreRecord::find()
            ->where(['purchasableId' => $ticket->id])
            ->orderBy(['storeId' => SORT_ASC])
            ->one();

        foreach ($stores as $store) {
            $existing = PurchasableStoreRecord::findOne([
                'purchasableId' => $ticket->id,
                'storeId' => $store->id,
            ]);

            if ($existing) {
                continue;
            }

            $record = new PurchasableStoreRecord();
            $record->purchasableId = $ticket->id;
            $record->storeId = $store->id;

            if ($template) {
                $skip = ['id', 'purchasableId', 'storeId', 'shippingCategoryId', 'dateCreated', 'dateUpdated', 'uid'];
                $record->setAttributes($template->getAttributes(null, $skip), false);
                $record->shippingCategoryId = self::shippingCategoryIdForStore($ticket, $store->id);
            } else {
                self::populateRecordFromTicket($record, $ticket, $store->id);
            }

            try {
                $record->save(false);
            } catch (Throwable $e) {
                Craft::error(
                    'Failed saving commerce_purchasables_stores for ticket ' . $ticket->id . ', store ' . $store->id . ': ' . $e->getMessage(),
                    __METHOD__
                );
            }
        }
    }

    public static function syncTicketIdToAllStores(int $ticketId): void
    {
        if (!$ticketId) {
            return;
        }

        if (!Craft::$app->getDb()->tableExists('{{%commerce_purchasables_stores}}')) {
            return;
        }

        $stores = Commerce::getInstance()->getStores()->getAllStores();

        if ($stores->isEmpty()) {
            return;
        }

        $ticketData = self::ticketDataById($ticketId);

        if (!$ticketData) {
            return;
        }

        $template = PurchasableStoreRecord::find()
            ->where(['purchasableId' => $ticketId])
            ->orderBy(['storeId' => SORT_ASC])
            ->one();

        foreach ($stores as $store) {
            $existing = PurchasableStoreRecord::findOne([
                'purchasableId' => $ticketId,
                'storeId' => $store->id,
            ]);

            if ($existing) {
                continue;
            }

            $record = new PurchasableStoreRecord();
            $record->purchasableId = $ticketId;
            $record->storeId = $store->id;

            if ($template) {
                $skip = ['id', 'purchasableId', 'storeId', 'shippingCategoryId', 'dateCreated', 'dateUpdated', 'uid'];
                $record->setAttributes($template->getAttributes(null, $skip), false);
                $record->shippingCategoryId = self::shippingCategoryIdForStoreId($ticketData['shippingCategoryId'] ?? null, $store->id);
            } else {
                self::populateRecordFromTicketData($record, $ticketData, $store->id);
            }

            try {
                $record->save(false);
            } catch (Throwable $e) {
                Craft::error(
                    'Failed saving commerce_purchasables_stores for ticket ' . $ticketId . ', store ' . $store->id . ': ' . $e->getMessage(),
                    __METHOD__
                );
            }
        }
    }

    private static function populateRecordFromTicket(PurchasableStoreRecord $record, Ticket $ticket, int $storeId): void
    {
        $record->basePrice = (float)($ticket->basePrice ?? $ticket->getBasePrice());
        $record->basePromotionalPrice = $ticket->basePromotionalPrice;

        try {
            $record->stock = Commerce::getInstance()->getInventory()->getInventoryLevelsForPurchasable($ticket)->sum('availableTotal');
        } catch (Throwable) {
            $record->stock = null;
        }

        $record->inventoryTracked = Ticket::hasInventory() ? $ticket->inventoryTracked : false;
        $record->allowOutOfStockPurchases = $ticket->allowOutOfStockPurchases;
        $record->minQty = $ticket->minQty;
        $record->maxQty = $ticket->maxQty;
        $record->promotable = $ticket->promotable;
        $record->availableForPurchase = $ticket->availableForPurchase;
        $record->freeShipping = $ticket->freeShipping;
        $record->shippingCategoryId = self::shippingCategoryIdForStore($ticket, $storeId);

        if ($record->hasAttribute('hasUnlimitedStock')) {
            $record->hasUnlimitedStock = false;
        }
    }

    private static function populateRecordFromTicketData(PurchasableStoreRecord $record, array $ticketData, int $storeId): void
    {
        $record->basePrice = (float)($ticketData['price'] ?? 0);
        $record->basePromotionalPrice = null;
        $record->stock = null;
        $record->inventoryTracked = Ticket::hasInventory();
        $record->allowOutOfStockPurchases = false;
        $record->minQty = $ticketData['minQty'] !== null ? (int)$ticketData['minQty'] : null;
        $record->maxQty = $ticketData['maxQty'] !== null ? (int)$ticketData['maxQty'] : null;
        $record->promotable = (bool)($ticketData['promotable'] ?? true);
        $record->availableForPurchase = true;
        $record->freeShipping = true;
        $record->shippingCategoryId = self::shippingCategoryIdForStoreId($ticketData['shippingCategoryId'] ?? null, $storeId);

        if ($record->hasAttribute('hasUnlimitedStock')) {
            $record->hasUnlimitedStock = false;
        }
    }

    private static function shippingCategoryIdForStore(Ticket $ticket, int $storeId): int
    {
        return self::shippingCategoryIdForStoreId($ticket->getEvent()?->getType()?->shippingCategoryId ?? null, $storeId);
    }

    private static function shippingCategoryIdForStoreId(?int $preferredId, int $storeId): int
    {
        $shippingCategories = Commerce::getInstance()->getShippingCategories();

        if ($preferredId && ($category = $shippingCategories->getShippingCategoryById($preferredId))) {
            $forStore = $shippingCategories->getAllShippingCategories($storeId)->all();
            $ids = array_map(fn($c) => $c->id, $forStore);

            if (in_array($preferredId, $ids, true)) {
                return $preferredId;
            }
        }

        $default = $shippingCategories->getDefaultShippingCategory($storeId);

        if ($default) {
            return $default->id;
        }

        $forStore = $shippingCategories->getAllShippingCategories($storeId)->all();

        if (!empty($forStore)) {
            return reset($forStore)->id;
        }

        $all = $shippingCategories->getAllShippingCategories()->all();
        $fallback = reset($all);

        if ($fallback) {
            return $fallback->id;
        }

        throw new \RuntimeException('Unable to resolve a Commerce shipping category for store ' . $storeId);
    }

    private static function ticketDataById(int $ticketId): ?array
    {
        $ticketData = (new Query())
            ->select([
                'price' => 'ticketTypes.price',
                'minQty' => 'ticketTypes.minQty',
                'maxQty' => 'ticketTypes.maxQty',
                'promotable' => 'ticketTypes.promotable',
                'shippingCategoryId' => 'eventTypes.shippingCategoryId',
            ])
            ->from(['tickets' => '{{%events_tickets}}'])
            ->leftJoin(['ticketTypes' => '{{%events_ticket_types}}'], '[[ticketTypes.id]] = [[tickets.typeId]]')
            ->leftJoin(['events' => '{{%events_events}}'], '[[events.id]] = [[tickets.eventId]]')
            ->leftJoin(['eventTypes' => '{{%events_event_types}}'], '[[eventTypes.id]] = [[events.typeId]]')
            ->where(['tickets.id' => $ticketId])
            ->one();

        return $ticketData ?: null;
    }
}
