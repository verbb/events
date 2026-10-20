<?php
namespace verbb\events\gql\resolvers;

use verbb\events\elements\db\EventQuery;
use verbb\events\elements\Event;
use verbb\events\elements\Session;
use verbb\events\helpers\Gql as GqlHelper;

use Craft;
use craft\elements\db\ElementQuery;
use craft\elements\ElementCollection;
use craft\gql\base\ElementResolver;
use craft\helpers\Db;

class SessionResolver extends ElementResolver
{
    // Static Methods
    // =========================================================================

    public static function prepareQuery(mixed $source, array $arguments, $fieldName = null): mixed
    {
        if ($source === null) {
            $query = Session::find();
        } else {
            $query = $source->$fieldName;
        }

        if (!$query instanceof ElementQuery) {
            return $query;
        }

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        if (!GqlHelper::canQueryEvents()) {
            return ElementCollection::empty();
        }

        // Craft applies joins to both query stages, so mixed owner IDs cannot reload a hidden owner.
        $query->innerJoin(['session_visible_events' => self::_getVisibleEventsQuery()->select([
            'id' => 'events_events.id',
            'siteId' => 'elements_sites.siteId',
        ])], '[[session_visible_events.id]] = [[elements_owners.ownerId]] AND [[session_visible_events.siteId]] = [[elements_sites.siteId]]');

        return $query;
    }

    public static function resolveEvent(Session $session): ?Event
    {
        $event = $session->getEvent();

        if (!$event) {
            return null;
        }

        $eventId = $event->id;

        // Craft's token-authorized previews replace canonical events with registered placeholders.
        if (Craft::$app->getElements()->getPlaceholderElement($event->getCanonicalId(), $event->siteId) === $event) {
            $eventId = $event->getCanonicalId();
        }

        return self::_getVisibleEventsQuery()
            ->id($eventId)
            ->andWhere(['elements_sites.siteId' => $event->siteId])
            ->exists() ? $event : null;
    }

    private static function _getVisibleEventsQuery(): EventQuery
    {
        $pairs = GqlHelper::extractAllowedEntitiesFromSchema();
        $siteIds = array_map(fn($site) => $site->id, GqlHelper::getAllowedSites());
        $typeIds = array_values(Db::idsByUids('{{%events_event_types}}', $pairs['eventsEventTypes'] ?? []));

        return GqlHelper::getEventVisibilityQuery('*')
            ->andWhere(['elements_sites.siteId' => $siteIds])
            ->typeId($typeIds)
            ->provisionalDrafts(GqlHelper::canQueryDrafts() ? null : false);
    }
}
