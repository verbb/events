<?php
namespace verbb\events\migrations;

use verbb\events\elements\PurchasedTicket;
use verbb\events\records\PurchasedTicketRecord;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\MigrationHelper;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\StringHelper;

class m200107_000000_purchased_tickets_elements extends Migration
{
    public function safeUp()
    {
        // Convert models into elements
        $purchasedTickets = (new Query())
            ->select(['*'])
            ->from('{{%events_purchasedtickets}}')
            ->all();

        $elementsService = Craft::$app->getElements();

        foreach ($purchasedTickets as $purchasedTicket) {
            $element = new PurchasedTicket();
            $element->eventId = $purchasedTicket['eventId'];
            $element->ticketId = $purchasedTicket['ticketId'];
            $element->orderId = $purchasedTicket['orderId'];
            $element->lineItemId = $purchasedTicket['lineItemId'];
            $element->ticketSku = $purchasedTicket['ticketSku'];
            $element->checkedIn = $purchasedTicket['checkedIn'];
            $element->checkedInDate = $purchasedTicket['checkedInDate'];

            $elementsService->saveElement($element, false);

            // Delete the new record, created in the elements' afterSave
            $record = PurchasedTicketRecord::findOne($element->id);
            $record->delete();

            // Assign the new element ID back to the original record
            $this->update('{{%events_purchasedtickets}}', ['id' => $element->id], ['id' => $purchasedTicket['id']]);

            // Also directly update the dateCreated/dateUpdated for the element
            $this->update('{{%elements}}', ['dateCreated' => $purchasedTicket['dateCreated'], 'dateUpdated' => $purchasedTicket['dateUpdated']], ['id' => $element->id]);
        }

        // All going well, we can now apply the foreign key
        $this->addForeignKey(null, '{{%events_purchasedtickets}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);

        return true;
    }

    public function safeDown()
    {
        echo "m200107_000000_purchased_tickets_elements cannot be reverted.\n";
        return false;
    }
}
