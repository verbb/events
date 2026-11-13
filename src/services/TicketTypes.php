<?php
namespace verbb\events\services;

use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\elements\TicketType;
use verbb\events\helpers\Gql as GqlHelper;

use Craft;
use craft\base\GqlInlineFragmentFieldInterface;
use craft\gql\types\QueryArgument;

use yii\base\Component;

use GraphQL\Type\Definition\Type;

class TicketTypes extends Component
{
    // Properties
    // =========================================================================

    private array $_contentFieldCache = [];


    // Public Methods
    // =========================================================================

    public function getAllTicketTypes(): array
    {
        return TicketType::find()->limit(null)->all();
    }

    public function getEditableTicketTypes(): array
    {
        $user = Craft::$app->getUser()->getIdentity();

        if (!$user) {
            return [];
        }

        // Ticket types belong to events, so editing rights come from each event's type.
        $eventTypeIds = [];

        foreach (Events::$plugin->getEventTypes()->getAllEventTypes() as $eventType) {
            if ($user->can("events-editTicketTypes:{$eventType->uid}")) {
                $eventTypeIds[] = $eventType->id;
            }
        }

        if (!$eventTypeIds) {
            return [];
        }

        $eventIds = Event::find()->typeId($eventTypeIds)->status(null)->limit(null)->ids();

        if (!$eventIds) {
            return [];
        }

        return TicketType::find()->eventId($eventIds)->status(null)->limit(null)->all();
    }

    public function getAllTicketTypesByEventId(int $eventId, int $siteId = null, bool $includeDisabled = true): array
    {
        $ticketTypeQuery = TicketType::find()
            ->eventId($eventId)
            ->limit(null)
            ->siteId($siteId);

        if ($includeDisabled) {
            $ticketTypeQuery->status(null);
        }

        return $ticketTypeQuery->all();
    }

    public function getTicketTypeById(int $id, int $siteId = null): ?TicketType
    {
        return TicketType::find()->id($id)->site($siteId)->status(null)->one();
    }

    public function getTicketTypeGqlContentArguments(): array
    {
        if (empty($this->_contentFieldCache)) {
            $contentArguments = [];

            foreach (Events::$plugin->getEventTypes()->getAllEventTypes() as $eventType) {
                if (!GqlHelper::isSchemaAwareOf(TicketType::gqlScopesByContext($eventType))) {
                    continue;
                }

                $fieldLayout = $eventType->getTicketTypeFieldLayout();

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
}
