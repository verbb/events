<?php
namespace verbb\events\models;

use verbb\events\elements\Event;

use craft\base\Model;

class Settings extends Model
{
    // Properties
    // =========================================================================

    public string $pluginName = 'Events';
    public string $defaultEventIndexStatus = '';
    public string $ticketPdfPath = 'shop/_pdf/tickets';
    public string $ticketPdfFilenameFormat = 'Tickets-{number}';
    public bool $checkinLogin = false;
    public string $checkinTemplate = '';
    public bool $ticketsShippable = false;
    public array $attachPdfToEmails = [];
    public bool $applyPendingTicketUpdates = false;
    public array $releaseCapacityOrderStatusHandles = ['cancelled', 'canceled', 'refunded'];
    public int $purchasedTicketTrashRetentionDays = 30;

    public bool $pdfAllowRemoteImages = false;
    public string $pdfPaperSize = 'letter';
    public string $pdfPaperOrientation = 'portrait';


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = ['defaultEventIndexStatus', 'in', 'range' => array_merge([''], array_keys(Event::statuses()))];
        $rules[] = [['releaseCapacityOrderStatusHandles'], 'safe'];
        $rules[] = [['purchasedTicketTrashRetentionDays'], 'integer', 'min' => 0];

        return $rules;
    }

    public function setReleaseCapacityOrderStatusHandles(mixed $value): void
    {
        if (is_string($value)) {
            $value = array_values(array_filter(array_map('trim', explode(',', $value))));
        }

        $this->releaseCapacityOrderStatusHandles = $value ?: [];
    }

}
