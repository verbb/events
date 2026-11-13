# Custom Frequency Types

A frequency type controls how a session repeats. When an editor creates a session, they pick its **Frequency**, and Events creates the repeated sessions from it when the session is saved. Events includes Once, Daily, Weekly and Monthly. A custom frequency type is useful when your events follow a pattern those can’t express, such as a course that runs every weekday but never on weekends.

## Create the Frequency Class

Extend `verbb\events\base\Frequency`. It handles the repeat count, the **Recurring Ends** setting and the loop that generates dates, so your class only needs to say how far each occurrence moves from the first session.

This example repeats a session on every weekday, Monday to Friday, until the date or number of occurrences the editor chooses. Save it in your module, such as `modules/sitemodule/frequencies/Weekdays.php`.

```php
<?php
namespace modules\sitemodule\frequencies;

use verbb\events\base\Frequency;
use verbb\events\models\FrequencyRepeatEnd;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Cp;

use DateTime;

class Weekdays extends Frequency
{
    public static function id(): string
    {
        return 'weekdays';
    }

    public static function displayName(): string
    {
        return Craft::t('site', 'Every Weekday');
    }

    public static function isRecurring(): bool
    {
        return true;
    }

    public function setNextRecurringDate(DateTime &$startDate, DateTime &$endDate, int $occurrences): void
    {
        // Keep the session's length when moving it
        $duration = $startDate->diff($endDate);
        $weekdays = 0;

        // Occurrence 0 is the original session, so move forward one weekday per occurrence
        while ($weekdays < $occurrences) {
            $startDate->modify('+1 day');

            if ((int)$startDate->format('N') <= 5) {
                $weekdays++;
            }
        }

        $endDate = (clone $startDate)->add($duration);
    }

    public function getInputHtml(?ElementInterface $element = null): string
    {
        return Cp::selectFieldHtml([
            'label' => Craft::t('site', 'Recurring Ends'),
            'name' => 'frequencyData[weekdays][repeatEnd][type]',
            'value' => $this->repeatEnd->type,
            'options' => FrequencyRepeatEnd::getTypeOptions(),
        ]) .
        Cp::dateFieldHtml([
            'label' => Craft::t('site', 'End Date'),
            'name' => 'frequencyData[weekdays][repeatEnd][date]',
            'value' => $this->repeatEnd->date,
        ]) .
        Cp::textFieldHtml([
            'label' => Craft::t('site', 'Occurrences'),
            'name' => 'frequencyData[weekdays][repeatEnd][count]',
            'value' => $this->repeatEnd->count,
            'type' => 'number',
            'min' => 1,
        ]);
    }
}
```

Choosing **After** in **Recurring Ends** uses the occurrences count, and **On Date** uses the end date.

## Register the Frequency

Events finds frequencies through the `registerFrequencyTypes` event. Register your class from your module or plugin’s `init()` method.

```php
use craft\events\RegisterComponentTypesEvent;
use modules\sitemodule\frequencies\Weekdays;
use verbb\events\services\Sessions;
use yii\base\Event;

Event::on(Sessions::class, Sessions::EVENT_REGISTER_FREQUENCY_TYPES, function(RegisterComponentTypesEvent $event) {
    $event->types[] = Weekdays::class;
});
```

The frequency then appears in the **Frequency** dropdown when creating a session.

## Read Settings from the Form

The frequency’s inputs are posted as `frequencyData[<id>][<setting>]`, where `<id>` is the value your `id()` method returns. Events only copies settings that are safe attributes of your class, meaning properties with a validation rule. The base class already has rules for `repeatCount` and `repeatEnd`. If you add your own property, add a rule for it in `defineRules()` too, or its posted value is ignored.

```php
public array $skipDates = [];

protected function defineRules(): array
{
    $rules = parent::defineRules();
    $rules[] = [['skipDates'], 'safe'];

    return $rules;
}
```

Events allows up to 1,000 occurrences for a session, available as `Frequency::MAX_OCCURRENCES`, and shows a validation error when a frequency would create more.

## Methods

::: reference
### `id()`

Returns the frequency’s unique identifier, used as its value in the **Frequency** dropdown and in the posted form data. Your class must implement it.

**Returns:** `string`
:::

::: reference
### `displayName()`

Returns the frequency’s name, as shown in the **Frequency** dropdown.

**Returns:** `string`
:::

::: reference
### `isRecurring()`

Returns whether the frequency creates repeated sessions. The base class returns `false`, which treats the session as a one-off, so return `true` from a frequency that repeats.

**Returns:** `bool`
:::

::: reference
### `setNextRecurringDate()`

Moves the start and end dates to an occurrence of the session. Events passes copies of the first session’s dates, along with the occurrence number, starting at 0 for the original session. Change both dates in place.

**Arguments:** `DateTime &$startDate`, `DateTime &$endDate`, `int $occurrences`

**Returns:** `void`
:::

::: reference
### `getInputHtml()`

Returns the HTML for the frequency’s settings, shown below the **Frequency** dropdown when it’s selected. Name inputs `frequencyData[<id>][<setting>]`. The base class returns an empty string.

**Arguments:** `?ElementInterface $element = null`, the session being edited

**Returns:** `string`
:::

::: reference
### `shouldContinueGenerating()`

Returns whether Events should keep creating occurrences. The base class follows the **Recurring Ends** setting, stopping after the chosen number of occurrences or once the start date passes the chosen end date. Override it to add your own stopping rule.

**Arguments:** `DateTime $startDate`, `DateTime $endDate`, `int $occurrences`

**Returns:** `bool`
:::

::: reference
### `getRecurringSessionDates()`

Returns the start and end dates of every repeated session, not including the original. Each item is an array with `startDate` and `endDate` keys. The base class builds it from `setNextRecurringDate()` and `shouldContinueGenerating()`, so you rarely need to override it.

**Arguments:** `Session $session`

**Returns:** `array`
:::
