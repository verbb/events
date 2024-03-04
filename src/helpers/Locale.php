<?php
namespace verbb\events\helpers;

use Craft;

class Locale
{
    // Static Methods
    // =========================================================================

    public static function switchAppLanguage(string $toLanguage, ?string $formattingLocale = null): void
    {
        Craft::$app->language = $toLanguage;
        
        $locale = Craft::$app->getI18n()->getLocaleById($toLanguage);
        
        Craft::$app->set('locale', $locale);

        if ($formattingLocale !== null) {
            $locale = Craft::$app->getI18n()->getLocaleById($formattingLocale);
        }

        Craft::$app->set('formattingLocale', $locale);
    }
}
