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

    public bool $pdfAllowRemoteImages = false;
    public string $pdfPaperSize = 'letter';
    public string $pdfPaperOrientation = 'portrait';


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = ['defaultEventIndexStatus', 'in', 'range' => array_merge([''], array_keys(Event::statuses()))];

        return $rules;
    }

}
