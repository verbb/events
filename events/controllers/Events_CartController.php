<?php
namespace Craft;

class Events_CartController extends BaseController
{
    protected $allowAnonymous = true;

    //
    // Actions here are derived from Commerce_CartController.php
    //

    /**
     * Custom controller action to handle multiple add-to-cart and assure that
     * users cannot book more tickets than available
     */
    public function actionAdd()
    {
        $this->requirePostRequest();

        $errors = array();
        $items = craft()->request->getPost('item');
        $eventId = craft()->request->getPost('event');
        $cart = craft()->commerce_cart->getCart();

        if (!isset($items)) {
//            craft()->urlManager->setRouteVariables(['error' => 'You must select some items.']);
            craft()->userSession->setError('You must select some items');
        } else {
            // Make sure we can only add the selected tickets according to how many left for the event
            $totalItems = array();

            foreach ($items as $key => $item) {
                if (isset($item['qty'])) {
                    if ((int)$item['qty'] > 0) {

                        if (isset($totalItems[$eventId])) {
                            $totalItems[$eventId] = $totalItems[$eventId] + (int)$item['qty'];
                        } else {
                            $totalItems[$eventId] = (int)$item['qty'];
                        }
                    }
                }
            }

            // Check if this user already has the max number of tickets in their cart already
            if (count($cart) > 0) {
                foreach ($cart->lineItems as $key => $lineItem) {

                    // Check if item is an Event item
                    if ($lineItem->purchasable instanceof Events_EventModel) {
                        $event = EventsHelper::getEventsService()->getEventById($lineItem->purchasable->product->id);

                        if (isset($totalItems[$event->id])) {
                            $totalItems[$event->id] += (int)$lineItem->qty;
                        }
                    }
                }
            }

            // Check the total quantity of tickets for all event provided doesn't exceed max
            foreach ($totalItems as $eventId => $qty) {
                $event = EventsHelper::getEventsService()->getEventById($eventId);

                $purchasedTickets = EventsHelper::getPurchasedTicketsService()->getAllByAttributes(['eventId' => $event->id]);
                $availableTickets = $event->capacity - count($purchasedTickets);

                if ($qty > $availableTickets) {
                    $errors[] = 'Please change ticket quantity for ' . $event->title;
                }
            }

            // Do some cart-adding!
            if (!$errors) {
                foreach ($items as $key => $item) {
                    $purchasableId = $key;
                    $note = isset($item['note']) ? $item['note'] : '';
                    $options = isset($item['options']) ? $item['options'] : [];
                    $qty = isset($item['qty']) ? (int)$item['qty'] : 0;

                    $cart->setContentFromPost('fields');

                    if ($qty != 0) {
                        $error = null;
                        if (!craft()->commerce_cart->addToCart($cart, $purchasableId, $qty, $note, $options, $error)) {
                            $errors[] = $error;
                        }
                    }
                }
            }

            if ($errors) {
                foreach($errors as $error) {
                    craft()->userSession->setError($error);
                }
//                craft()->urlManager->setRouteVariables(['error' => $errors]);
            } else {
                craft()->userSession->setFlash('commerce', 'Product has been added');
                $this->redirectToPostedUrl();
            }
        }
    }
}