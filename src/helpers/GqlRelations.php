<?php
namespace verbb\events\helpers;

use verbb\events\elements\Event;
use verbb\events\elements\Session;
use verbb\events\elements\Ticket;
use verbb\events\elements\TicketType;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Db;

class GqlRelations
{
    // Static Methods
    // =========================================================================

    public static function visible(Event|Session|Ticket|TicketType|null $element): ?ElementInterface
    {
        if (!$element) {
            return null;
        }

        $siteIds = array_map(static fn($site) => $site->id, Gql::getAllowedSites());

        if ($element::isLocalized() && !in_array($element->siteId, $siteIds, true)) {
            return null;
        }

        $id = $element->id;

        // Only Craft's registered, token-authorized preview can stand in for a canonical element.
        if (Craft::$app->getElements()->getPlaceholderElement($element->getCanonicalId(), $element->siteId) === $element) {
            $id = $element->getCanonicalId();
        }

        $query = $element::find()
            ->id($id)
            ->siteId($element->siteId)
            ->drafts(Gql::canQueryDrafts() ? null : false)
            ->provisionalDrafts(Gql::canQueryDrafts() ? null : false)
            ->revisions(Gql::canQueryRevisions() ? null : false);

        if (Gql::canQueryInactiveElements()) {
            $query->status(null);
        }

        if ($element instanceof Event) {
            $pairs = Gql::extractAllowedEntitiesFromSchema();
            $typeIds = array_values(Db::idsByUids('{{%events_event_types}}', $pairs['eventsEventTypes'] ?? []));
            $query->typeId($typeIds)->andWhere(['elements_sites.siteId' => $siteIds]);
        } else {
            $event = $element->getEvent();

            if (!self::visible($event)) {
                return null;
            }

            if ($element instanceof Session || $element instanceof TicketType) {
                // A shared nested element must be checked in the same owner context that will be returned.
                $query->ownerId($event->id);
            }

            if ($element instanceof Ticket && (!self::visible($element->getSession()) || !self::visible($element->getType()))) {
                return null;
            }
        }

        // Recheck already-loaded objects against this schema without changing trusted PHP getters.
        return $query->exists() ? $element : null;
    }
}
