<?php
namespace verbb\events\fieldlayoutelements;

use verbb\events\Events;
use verbb\events\elements\Session;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use craft\helpers\Cp;
use craft\helpers\Html;

use yii\base\InvalidArgumentException;

class SessionFrequencyField extends BaseNativeField
{
    // Properties
    // =========================================================================

    public bool $required = false;
    public bool $mandatory = true;
    public string $attribute = 'frequency';


    // Protected Methods
    // =========================================================================

    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('events', 'Frequency');
    }

    protected function defaultInstructions(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('events', 'How often will this session occur?');
    }

    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        $view = Craft::$app->getView();

        if (!$element instanceof Session) {
            throw new InvalidArgumentException('SessionFrequencyField can only be used in session field layouts.');
        }

        $containerId = 'events-session-frequency-field';

        // Only show this when creating new sessions
        if (!$element->getIsFresh()) {
            return null;
        }

        $html = [];

        $html[] = Html::beginTag('div', ['id' => $containerId]);

        $html[] = Cp::selectFieldHtml([
            'id' => 'frequency-data-type',
            'name' => 'frequencyData[type]',
            'value' => $element->frequency::id(),
            'toggle' => true,
            'targetPrefix' => '.frequency-',
            'options' => Events::$plugin->getSessions()->getFrequencyTypeOptions(),
            'fieldAttributes' => [
                'data-error-key' => 'frequencyData.type',
            ],
        ]);

        foreach (Events::$plugin->getSessions()->getFrequencies() as $id => $frequency) {
            $frequencyModel = $element->frequency::id() === $id ? $element->frequency : $frequency;

            $html[] = Html::beginTag('div', [
                'class' => [
                    'frequency-' . $id,
                    ($element->frequency::id() !== $id ? 'hidden' : ''),
                ],
            ]);

            $html[] = $frequencyModel->getInputHtml($element);
            $html[] = Html::endTag('div');
        }

        $html[] = Html::endTag('div');

        return implode('', $html);
    }
}
