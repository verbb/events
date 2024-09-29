<?php
namespace verbb\events\fieldlayoutelements;

use verbb\events\elements\Session;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use craft\helpers\Cp;
use craft\helpers\Html;

use yii\base\InvalidArgumentException;

use DateTime;

class SessionStartDateTimeField extends BaseNativeField
{
    // Properties
    // =========================================================================

    public bool $required = true;
    public bool $mandatory = true;
    public string $attribute = 'startDate';
    public ?DateTime $defaultTime = null;


    // Protected Methods
    // =========================================================================

    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('events', 'Start Date');
    }

    protected function defaultInstructions(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('events', 'The start date/time for the session.');
    }
    
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

        return $html;
    }

    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof Session) {
            throw new InvalidArgumentException(static::class . ' can only be used in session field layouts.');
        }

        // Annoyingly, we need to construct the date/time input ourselves to have a separate date/time default value.
        $config = [
            'id' => 'start-date',
            'name' => 'startDate',
            'fieldset' => true,
        ];

        $dateConfig = $config + [
            'hasOuterContainer' => true,
            'isDateTime' => true,
            'value' => $element?->startDate?->format('c') ?? null,
        ];

        $timeConfig = $config + [
            'hasOuterContainer' => true,
            'isDateTime' => true,
            'outputLocaleParam' => false,
            'outputTzParam' => false,
            'value' => $element?->startDate?->format('c') ?? $this->defaultTime?->format('c') ?? null,
        ];

        return Cp::fieldHtml(Html::tag('div', Cp::dateHtml($dateConfig) . Cp::timeHtml($timeConfig), [
            'class' => 'datetimewrapper',
        ]), $config);
    }
}
