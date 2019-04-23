<?php
namespace verbb\events\helpers;

use verbb\events\Events;

use Craft;

class TicketHelper
{
    // Properties
    // =========================================================================
    
    const TICKET_KEY_CHARACTERS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';


    // Public Methods
    // =========================================================================

    public static function getTicketRowHtml(): array
    {
        $originalNamespace = Craft::$app->getView()->getNamespace();
        $namespace = Craft::$app->getView()->namespaceInputName('tickets[__ROWID__]', $originalNamespace);
        Craft::$app->getView()->setNamespace($namespace);

        Craft::$app->getView()->startJsBuffer();

        $variables = [
            'ticket' => [],
        ];

        $template = Craft::$app->getView()->renderTemplate('events/_includes/ticket-row', $variables);

        $bodyHtml = Craft::$app->getView()->namespaceInputs($template);
        $footHtml = Craft::$app->getView()->clearJsBuffer();

        Craft::$app->getView()->setNamespace($originalNamespace);
        
        return [
            'bodyHtml' => $bodyHtml,
            'footHtml' => $footHtml,
        ];
    }

    public static function generateTicketSKU()
    {
        $codeAlphabet = self::TICKET_KEY_CHARACTERS;
        $keyLength = Events::getInstance()->getSettings()->ticketSKULength;
        $ticketKey = '';
        
        for ($i = 0; $i < $keyLength; $i++) {
            $ticketKey .= $codeAlphabet[mt_rand(0, strlen($codeAlphabet) - 1)];
        }

        return $ticketKey;
    }
}