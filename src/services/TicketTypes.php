<?php
namespace verbb\events\services;

use verbb\events\Events;
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

    public function getTicketTypeById(int $ticketTypeId, int $siteId = null): ?TicketType
    {
        return Craft::$app->getElements()->getElementById($ticketTypeId, TicketType::class, $siteId);
    }

    public function getTicketTypeGqlContentArguments(): array
    {
        if (empty($this->_contentFieldCache)) {
            $contentArguments = [];

            foreach (Events::$plugin->getEventTypes()->getAllEventTypes() as $eventType) {
                if (!GqlCommerceHelper::isSchemaAwareOf(TicketType::gqlScopesByContext($eventType))) {
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
