<?php
namespace verbb\events\fieldlayoutelements;

use verbb\events\elements\Session;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\StringHelper;

use yii\base\InvalidArgumentException;

use DateTime;

class SessionDateTimeField extends BaseNativeField
{
    // Properties
    // =========================================================================

    public bool $required = true;
    public bool $mandatory = true;
    public ?DateTime $defaultTime = null;
    public bool $showTimeZone = false;


    // Protected Methods
    // =========================================================================
    
    protected function settingsHtml(): ?string
    {
        $html = parent::settingsHtml();

        $html .= Cp::timeFieldHtml([
            'label' => Craft::t('events', 'Default Time'),
            'instructions' => Craft::t('events', 'Set a default time for this field for brand-new sessions.'),
            'id' => 'default-time',
            'name' => 'defaultTime',
            'value' => $this->defaultTime ? $this->defaultTime->format('c') : null,
        ]);

        $html .= Cp::lightswitchFieldHtml([
            'label' => Craft::t('events', 'Show Time Zone'),
            'instructions' => Craft::t('events', 'Whether to show a time zone picker alongside date/time pickers.'),
            'id' => 'show-time-zone',
            'name' => 'showTimeZone',
            'on' => $this->showTimeZone,
        ]);

        return $html;
    }

    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof Session) {
            throw new InvalidArgumentException(static::class . ' can only be used in session field layouts.');
        }

        $value = $element?->{$this->attribute} ?? null;
        $timezone = $this->showTimeZone && $value ? $value->getTimezone()->getName() : Craft::$app->getTimeZone();

        // Annoyingly, we need to construct the date/time input ourselves to have a separate date/time default value.
        $config = [
            'id' => StringHelper::toKebabCase($this->attribute),
            'name' => $this->attribute,
            'fieldset' => true,
        ];

        $dateConfig = $config + [
            'hasOuterContainer' => true,
            'isDateTime' => true,
            'value' => $value?->format('c') ?? null,
        ];

        $timeConfig = $config + [
            'hasOuterContainer' => true,
            'isDateTime' => true,
            'outputLocaleParam' => false,
            'outputTzParam' => false,
            'value' => $value?->format('c') ?? $this->defaultTime?->format('c') ?? null,
        ];

        $components = [
            Cp::dateHtml($dateConfig),
            Cp::timeHtml($timeConfig),
        ];

        if ($this->showTimeZone) {
            $components[] = Cp::renderTemplate('_includes/forms/timeZone.twig', [
                'name' => "$this->attribute[timezone]",
                'value' => $timezone,
            ]);
        }

        return Cp::fieldHtml(Html::tag('div', implode('', $components), [
            'class' => 'datetimewrapper',
        ]), $config);
    }
}
