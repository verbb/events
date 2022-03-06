<?php
namespace verbb\events\helpers;

use verbb\events\Events;

use Craft;

class TicketHelper
{
    // Properties
    // =========================================================================

    public const TICKET_KEY_CHARACTERS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';


    // Static Methods
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

    public static function generateTicketSKU(): string
    {
        $codeAlphabet = self::TICKET_KEY_CHARACTERS;
        $keyLength = Events::$plugin->getSettings()->ticketSKULength;
        $ticketKey = '';

        for ($i = 0; $i < $keyLength; $i++) {
            $ticketKey .= $codeAlphabet[mt_rand(0, strlen($codeAlphabet) - 1)];
        }

        return $ticketKey;
    }
}