<?php
namespace verbb\events\integrations\klaviyoconnect;

use verbb\events\elements\Ticket;

use yii\base\Component;

use fostercommerce\klaviyoconnect\events\AddLineItemCustomPropertiesEvent;

class KlaviyoConnect extends Component
{
    // Public Methods
    // =========================================================================

    public function addLineItemCustomProperties(AddLineItemCustomPropertiesEvent $event): void
    {
        $eventName = $event->event;
        $order = $event->order;
        $lineItem = $event->lineItem;

        if (is_a($lineItem->purchasable, Ticket::class)) {
            $eventElement = $lineItem->purchasable->event ?? [];

            if ($eventElement) {
                $event->properties = [
                    'ProductName' => $eventElement->title,
                    'Slug' => $lineItem->purchasable->event->slug,
                    'ProductURL' => $eventElement->getUrl(),
                    'ItemPrice' => $lineItem->price,
                    'RowTotal' => $lineItem->subtotal,
                    'Quantity' => $lineItem->qty,
                    'SKU' => $lineItem->purchasable->sku,
                ];
            }
        }
    }
}
