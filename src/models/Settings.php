<?php
namespace verbb\events\models;

use craft\base\Model;

class Settings extends Model
{
    // Properties
    // =========================================================================

    public $pluginName = 'Events';
    public $ticketSKULength = 10;
    public $ticketPdfPath = 'shop/_pdf/tickets';
    public $ticketPdfFilenameFormat = 'Tickets-{number}';
    public $checkinLogin = false;
    public $checkinTemplate = '';

    public $pdfAllowRemoteImages = false;
    public $pdfPaperSize = 'letter';
    public $pdfPaperOrientation = 'portrait';

}
