<?php
namespace verbb\events\services;

use verbb\events\base\FrequencyInterface;
use verbb\events\elements\Session;
use verbb\events\frequencies;
use verbb\events\helpers\Gql as GqlHelper;

use Craft;
use craft\base\Event;
use craft\base\GqlInlineFragmentFieldInterface;
use craft\events\RegisterComponentTypesEvent;
use craft\gql\types\QueryArgument;
use craft\helpers\Component as ComponentHelper;

use yii\base\Component;

use GraphQL\Type\Definition\Type;

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
    private array $_contentFieldCache = [];


    // Public Methods
    // =========================================================================

    public function getAllSessionsByEventId(int $eventId, int $siteId = null, bool $includeDisabled = true): array
    {
        $sessionQuery = Session::find()
            ->eventId($eventId)
            ->limit(null)
            ->siteId($siteId);

        if ($includeDisabled) {
            $sessionQuery->status(null);
        }

        return $sessionQuery->all();
    }

    public function getSessionById(int $sessionId, int $siteId = null): ?Session
    {
        return Craft::$app->getElements()->getElementById($sessionId, Session::class, $siteId);
    }

    public function getSessionGqlContentArguments(): array
    {
        if (empty($this->_contentFieldCache)) {
            $contentArguments = [];

            foreach (Events::$plugin->getEventTypes()->getAllEventTypes() as $eventType) {
                if (!GqlCommerceHelper::isSchemaAwareOf(Session::gqlScopesByContext($eventType))) {
                    continue;
                }

                $fieldLayout = $eventType->getSessionFieldLayout();

                foreach ($fieldLayout->getCustomFields() as $contentField) {
                    if (!$contentField instanceof GqlInlineFragmentFieldInterface) {
                        $contentArguments[$contentField->handle] = [
                            'name' => $contentField->handle,
                            'type' => Type::listOf(QueryArgument::getType()),
                        ];
                    }
                }
            }

            $this->_contentFieldCache = $contentArguments;
        }

        return $this->_contentFieldCache;
    }

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
