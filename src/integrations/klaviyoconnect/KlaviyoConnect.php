<?php
namespace verbb\events\integrations\klaviyoconnect;

use verbb\events\elements\Event;
use verbb\events\elements\Ticket;

use Craft;

use yii\base\Component;

use fostercommerce\klaviyoconnect\events\AddLineItemCustomPropertiesEvent;

class KlaviyoConnect extends Component
{
    // Public Methods
    // =========================================================================

    public function addLineItemCustomProperties(AddLineItemCustomPropertiesEvent $e)
    {
        $eventName = $e->event;
        $order = $e->order;
        $lineItem = $e->lineItem;

        if (is_a($lineItem->purchasable, Ticket::class)) {
            $event = $lineItem->purchasable->event ?? [];

            if ($event) {
                $e->properties = [
                    'ProductName' => $event->title,
                    'Slug' => $lineItem->purchasable->event->slug,
                    'ProductURL' => $event->getUrl(),
                    'ItemPrice' => $lineItem->price,
                    'RowTotal' => $lineItem->subtotal,
                    'Quantity' => $lineItem->qty,
                    'SKU' => $lineItem->purchasable->sku,
                ];
            }
        }
    }
}
