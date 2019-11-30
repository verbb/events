<?php
namespace verbb\events\helpers;

use Craft;
use craft\db\Query;
use craft\helpers\Json;

class ProjectConfigData
{
    // Public Methods
    // =========================================================================


    // Project config rebuild methods
    // =========================================================================

    public static function rebuildProjectConfig(): array
    {
        $output = [];

        $output['eventTypes'] = self::_getEventTypeData();

        return $output;
    }

    private static function _getEventTypeData(): array
    {
        $eventTypeRows = (new Query())
            ->select([
                'name',
                'handle',
                'fieldLayoutId',
                'hasTitleField',
                'titleLabel',
                'titleFormat',
                'hasTickets',
                'uid'
            ])
            ->from(['{{%events_eventtypes}} eventTypes'])
            ->all();

        $typeData = [];

        foreach ($eventTypeRows as $eventTypeRow) {
            $rowUid = $eventTypeRow['uid'];

            if (!empty($eventTypeRow['fieldLayoutId'])) {
                $layout = Craft::$app->getFields()->getLayoutById($eventTypeRow['fieldLayoutId']);

                if ($layout) {
                    $eventTypeRow['eventFieldLayouts'] = [$layout->uid => $layout->getConfig()];
                }
            }

            unset($eventTypeRow['uid'], $eventTypeRow['fieldLayoutId']);
            $eventTypeRow['siteSettings'] = [];
            $typeData[$rowUid] = $eventTypeRow;
        }

        $eventTypeSiteRows = (new Query())
            ->select([
                'eventtypes_sites.hasUrls',
                'eventtypes_sites.uriFormat',
                'eventtypes_sites.template',
                'sites.uid AS siteUid',
                'eventtypes.uid AS typeUid',
            ])
            ->from(['{{%events_eventtypes_sites}} eventtypes_sites'])
            ->innerJoin('{{%sites}} sites', '[[sites.id]] = [[eventtypes_sites.siteId]]')
            ->innerJoin('{{%events_eventtypes}} eventtypes', '[[eventtypes.id]] = [[eventtypes_sites.eventTypeId]]')
            ->all();

        foreach ($eventTypeSiteRows as $eventTypeSiteRow) {
            $typeUid = $eventTypeSiteRow['typeUid'];
            $siteUid = $eventTypeSiteRow['siteUid'];
            unset($eventTypeSiteRow['siteUid'], $eventTypeSiteRow['typeUid']);
            $typeData[$typeUid]['siteSettings'][$siteUid] = $eventTypeSiteRow;
        }

        return $typeData;
    }
}
