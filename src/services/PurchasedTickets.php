<?php
namespace verbb\events\services;

use verbb\events\models\PurchasedTicket;
use verbb\events\records\PurchasedTicketRecord;

use Craft;
use craft\db\Query;
use craft\events\SiteEvent;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\queue\jobs\ResaveElements;

use yii\base\Component;
use yii\base\Exception;

class PurchasedTickets extends Component
{

    // Properties
    // =========================================================================

    private $_fetchedAllPurchasedTickets = false;
    private $_allPurchasedTickets = [];


    // Public Methods
    // =========================================================================

    public function getAllPurchasedTickets($criteria = []): array
    {
        if (!$this->_fetchedAllPurchasedTickets) {
            $rows = $this->_createPurchasedTicketsQuery()->all();

            foreach ($rows as $row) {
                $this->_allPurchasedTickets[$row['id']] = new PurchasedTicket($row);
            }

            $this->_fetchedAllPurchasedTickets = true;
        }

        if ($criteria) {
            $rows = PurchasedTicketRecord::find()->where($criteria)->all();
            $purchasedTickets = [];

            foreach ($rows as $row) {
                $purchasedTickets[] = new PurchasedTicket($row);
            }

            return $purchasedTickets;
        }

        return $this->_allPurchasedTickets;
    }

    public function getPurchasedTicket($criteria = [])
    {
        $result = PurchasedTicketRecord::find()->where($criteria)->one();

        return new PurchasedTicket($result);
    }

    public function getPurchasedTicketById($id)
    {
        if (isset($this->_allPurchasedTickets[$id])) {
            return $this->_allPurchasedTickets[$id];
        }

        if ($this->_fetchedAllPurchasedTickets) {
            return null;
        }

        $result = $this->_createPurchasedTicketsQuery()
            ->where(['id' => $id])
            ->one();

        if (!$result) {
            return null;
        }

        return $this->_allPurchasedTickets[$id] = new PurchasedTicket($result);
    }

    public function checkInPurchasedTicket(PurchasedTicket $purchasedTicket)
    {
        $purchasedTicket->checkedIn = true;
        $purchasedTicket->checkedInDate = new \DateTime();

        $record = PurchasedTicketRecord::findOne($purchasedTicket->id);
        $record->checkedIn = $purchasedTicket->checkedIn;
        $record->checkedInDate = $purchasedTicket->checkedInDate;

        $record->save(false);
    }


    // Private methods
    // =========================================================================

    private function _createPurchasedTicketsQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'eventId',
                'ticketId',
                'orderId',
                'lineItemId',
                'ticketSku',
                'checkedIn',
                'checkedInDate',
            ])
            ->from(['{{%events_purchasedtickets}}']);
    }
}