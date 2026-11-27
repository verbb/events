<?php
namespace verbb\events\helpers;

use Craft;
use craft\base\ElementInterface;

/**
 * Shares the parent elements of a query's rows while that query populates them.
 *
 * Tickets and purchased tickets resolve their event, session, ticket type or ticket in `init()`, which used to run one
 * query per parent per row. Their queries now load each kind of parent once for the whole result and expose it here
 * only for the duration of `populate()`, so nothing outlives the query that loaded it and no later query can see stale
 * or mutated parents. Lookups use the same criteria as the services' `get*ById()` methods (current site, any status).
 *
 * @internal
 */
class ParentElements
{
    // Static Methods
    // =========================================================================

    /**
     * Loads parents by ID for each element class, skipping any already supplied in `$known`. IDs that don't resolve
     * are recorded as `null`, so rows pointing at a missing parent don't each query for it again.
     */
    public static function load(array $idsByClass, array $known = []): array
    {
        $parents = [];

        foreach ($idsByClass as $class => $ids) {
            $parents[$class] = $known[$class] ?? [];

            $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
            $missing = array_values(array_diff($ids, array_keys($parents[$class])));

            if (!$missing) {
                continue;
            }

            /* @var ElementInterface $class */
            $elements = $class::find()
                ->id($missing)
                ->site(null)
                ->status(null)
                ->limit(null)
                ->indexBy('id')
                ->all();

            foreach ($missing as $id) {
                $parents[$class][$id] = $elements[$id] ?? null;
            }
        }

        return $parents;
    }

    /**
     * Runs a query's population with the given parents available to `get()`, restoring any outer batch afterwards.
     */
    public static function populate(array $parents, callable $populate): mixed
    {
        $previous = self::$_batch;

        self::$_batch = [
            'siteId' => self::_currentSiteId(),
            'parents' => $parents,
        ];

        try {
            return $populate();
        } finally {
            self::$_batch = $previous;
        }
    }

    /**
     * Returns the batched parent (or `null` when the batch found none), or `false` when the ID isn't part of a batch
     * for the current site and the caller should look it up itself.
     */
    public static function get(string $class, ?int $id): ElementInterface|false|null
    {
        if (!$id || self::$_batch === null || !array_key_exists($id, self::$_batch['parents'][$class] ?? [])) {
            return false;
        }

        // A template switching sites mid-populate would expect parents from the new current site.
        if (self::$_batch['siteId'] !== self::_currentSiteId()) {
            return false;
        }

        return self::$_batch['parents'][$class][$id];
    }

    private static function _currentSiteId(): ?int
    {
        return Craft::$app->getSites()->getCurrentSite()?->id;
    }


    // Properties
    // =========================================================================

    private static ?array $_batch = null;
}
