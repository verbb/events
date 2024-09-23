<?php
namespace verbb\events\services;

use verbb\events\base\FrequencyInterface;
use verbb\events\frequencies;

use craft\base\Event;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\Component as ComponentHelper;

use yii\base\Component;

class Sessions extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_REGISTER_FREQUENCY_TYPES = 'registerFrequencyTypes';


    // Static Methods
    // =========================================================================

    public function getRegisteredFrequencyTypes(): array
    {
        $types = [
            frequencies\Once::class,
            frequencies\Daily::class,
            frequencies\Weekly::class,
            frequencies\Monthly::class,
        ];

        $event = new RegisterComponentTypesEvent([
            'types' => $types,
        ]);

        Event::trigger(self::class, self::EVENT_REGISTER_FREQUENCY_TYPES, $event);

        return $event->types;
    }

    public function getFrequencyTypeOptions(): array
    {
        $options = [];
        
        foreach ($this->getRegisteredFrequencyTypes() as $type) {
            $options[] = ['label' => $type::displayName(), 'value' => $type::id()];
        }
        
        return $options;
    }


    // Properties
    // =========================================================================

    private array $_frequencies = [];


    // Public Methods
    // =========================================================================

    public function getFrequencies(): array
    {
        if (!$this->_frequencies) {
            $this->_frequencies = [];
            $types = $this->getRegisteredFrequencyTypes();

            foreach ($types as $type) {
                $this->_frequencies[$type::id()] = ComponentHelper::createComponent([
                    'type' => $type,
                ], FrequencyInterface::class);
            }
        }

        return $this->_frequencies;
    }

    public function getFrequencyById(string $id): ?FrequencyInterface
    {
        return $this->getFrequencies()[$id] ?? null;
    }

}
