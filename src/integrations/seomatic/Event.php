<?php
namespace verbb\events\integrations\seomatic;

use verbb\events\Events as EventsPlugin;
use verbb\events\elements\Event as EventElement;
use verbb\events\services\EventTypes;

use Craft;
use craft\base\ElementInterface;
use craft\base\Model;
use craft\elements\db\ElementQueryInterface;

use nystudio107\seomatic\assetbundles\seomatic\SeomaticAsset;
use nystudio107\seomatic\helpers\PluginTemplate;
use nystudio107\seomatic\Seomatic;
use nystudio107\seomatic\base\SeoElementInterface;
use nystudio107\seomatic\helpers\ArrayHelper;
use nystudio107\seomatic\helpers\Config as ConfigHelper;
use nystudio107\seomatic\models\MetaBundle;

use yii\base\Event as YiiEvent;
use Exception;

class Event implements SeoElementInterface
{
    // Constants
    // =========================================================================

    public const META_BUNDLE_TYPE = 'event';

    public const ELEMENT_CLASSES = [
        EventElement::class,
    ];

    public const REQUIRED_PLUGIN_HANDLE = 'events';
    public const CONFIG_FILE_PATH = 'eventmeta/Bundle';


    // Static Methods
    // =========================================================================

    public static function getMetaBundleType(): string
    {
        return self::META_BUNDLE_TYPE;
    }

    public static function getElementClasses(): array
    {
        return self::ELEMENT_CLASSES;
    }

    public static function getElementRefHandle(): string
    {
        return EventElement::refHandle() ?? 'event';
    }

    public static function getRequiredPluginHandle(): string
    {
        return self::REQUIRED_PLUGIN_HANDLE;
    }

    public static function installEventHandlers(): void
    {
        $request = Craft::$app->getRequest();

        // Install for all non-console requests
        if (!$request->getIsConsoleRequest()) {
            YiiEvent::on(EventTypes::class, EventTypes::EVENT_AFTER_SAVE_EVENTTYPE, function($event) {
                if ($event->eventType !== null && $event->eventType->id !== null) {
                    Seomatic::$plugin->metaBundles->invalidateMetaBundleById(
                        Event::getMetaBundleType(),
                        $event->eventType->id,
                        $event->isNew
                    );

                    // Create the meta bundles for this Event Type if it's new
                    if ($event->isNew) {
                        Event::createContentMetaBundle($event->eventType);
                        Seomatic::$plugin->sitemaps->submitSitemapIndex();
                    }
                }
            });
        }

        // Install only for non-console Control Panel requests
        if ($request->getIsCpRequest() && !$request->getIsConsoleRequest()) {
            // Events sidebar
            Craft::$app->view->hook('events.edit.details', function(&$context) {
                $html = '';

                Seomatic::$view->registerAssetBundle(SeomaticAsset::class);

                $event = $context[self::getElementRefHandle()] ?? null;

                if ($event !== null && $event->uri !== null) {
                    Seomatic::$plugin->metaContainers->previewMetaContainers($event->uri, $event->siteId, true);

                    // Render our preview sidebar template
                    if (Seomatic::$settings->displayPreviewSidebar) {
                        $html .= PluginTemplate::renderPluginTemplate('_sidebars/event-preview.twig');
                    }
                }

                return $html;
            });
        }
    }

    public static function sitemapElementsQuery(MetaBundle $metaBundle): ElementQueryInterface
    {
        return EventElement::find()
            ->type($metaBundle->sourceHandle)
            ->siteId($metaBundle->sourceSiteId)
            ->limit($metaBundle->metaSitemapVars->sitemapLimit);
    }

    public static function sitemapAltElement(MetaBundle $metaBundle, int $elementId, int $siteId): ElementInterface|\yii\base\Model|array|null
    {
        return EventElement::find()
            ->id($elementId)
            ->siteId($siteId)
            ->limit(1)
            ->one();
    }

    public static function previewUri(string $sourceHandle, $siteId)
    {
        $uri = null;

        $element = EventElement::find()
            ->type($sourceHandle)
            ->siteId($siteId)
            ->one();

        if ($element) {
            $uri = $element->uri;
        }

        return $uri;
    }

    public static function fieldLayouts(string $sourceHandle): array
    {
        $layouts = [];
        $events = EventsPlugin::getInstance();

        if ($events !== null) {
            $layoutId = null;

            try {
                $eventType = $events->getEventTypes()->getEventTypeByHandle($sourceHandle);

                if ($eventType) {
                    $layoutId = $eventType->getFieldLayoutId();
                }
            } catch (Exception $e) {
                $layoutId = null;
            }

            if ($layoutId) {
                $layouts[] = Craft::$app->getFields()->getLayoutById($layoutId);
            }
        }

        return $layouts;
    }

    public static function typeMenuFromHandle(string $sourceHandle): array
    {
        return [];
    }

    public static function sourceModelFromId(int $sourceId)
    {
        $eventType = null;
        $events = EventsPlugin::getInstance();

        if ($events !== null) {
            $eventType = $events->getEventTypes()->getEventTypeById($sourceId);
        }

        return $eventType;
    }

    public static function sourceModelFromHandle(string $sourceHandle)
    {
        $eventType = null;
        $events = EventsPlugin::getInstance();

        if ($events !== null) {
            $eventType = $events->getEventTypes()->getEventTypeByHandle($sourceHandle);
        }

        return $eventType;
    }

    public static function mostRecentElement(Model $sourceModel, int $sourceSiteId)
    {
        return EventElement::find()
            ->type($sourceModel->handle)
            ->siteId($sourceSiteId)
            ->limit(1)
            ->orderBy(['elements.dateUpdated' => SORT_DESC])
            ->one();
    }

    public static function configFilePath(): string
    {
        return self::CONFIG_FILE_PATH;
    }

    public static function metaBundleConfig(Model $sourceModel): array
    {
        return ArrayHelper::merge(ConfigHelper::getConfigFromFile(self::configFilePath()), [
            'sourceId' => $sourceModel->id,
            'sourceName' => (string)$sourceModel->name,
            'sourceHandle' => $sourceModel->handle,
        ]);
    }

    public static function sourceIdFromElement(ElementInterface $element)
    {
        return $element->typeId;
    }

    public static function sourceHandleFromElement(ElementInterface $element): string
    {
        $sourceHandle = '';

        try {
            $sourceHandle = $element->getType()->handle;
        } catch (Exception $e) {
        }

        return $sourceHandle;
    }

    public static function createContentMetaBundle(Model $sourceModel): void
    {
        $sites = Craft::$app->getSites()->getAllSites();

        foreach ($sites as $site) {
            $seoElement = self::class;
            Seomatic::$plugin->metaBundles->createMetaBundleFromSeoElement($seoElement, $sourceModel, $site->id);
        }
    }

    public static function createAllContentMetaBundles(): void
    {
        $events = EventsPlugin::getInstance();

        if ($events !== null) {
            $eventTypes = $events->getEventTypes()->getAllEventTypes();

            foreach ($eventTypes as $eventType) {
                self::createContentMetaBundle($eventType);
            }
        }
    }
}
