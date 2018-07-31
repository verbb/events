<?php
namespace Craft;

class Events_EventElementType extends BaseElementType
{

    // Public Methods
    // =========================================================================

    public function getName()
    {
        return Craft::t('Events');
    }

    public function hasContent()
    {
        return true;
    }

    public function hasTitles()
    {
        return true;
    }

    public function hasStatuses()
    {
        return true;
    }

    public function isLocalized()
    {
        return true;
    }

    public function getSources($context = null)
    {
        if ($context == 'index') {
            $eventTypes = EventsHelper::getEventTypesService()->getEditableEventTypes();
            $editable = true;
        } else {
            $eventTypes = EventsHelper::getEventTypesService()->getAllEventTypes();
            $editable = false;
        }

        $eventTypeIds = [];

        foreach ($eventTypes as $eventType) {
            $eventTypeIds[] = $eventType->id;
        }

        $sources = [
            '*' => [
                'label' => Craft::t('All events'),
                'criteria' => [
                    'typeId' => $eventTypeIds,
                    'editable' => $editable
                ],
                //'defaultSort' => ['postDate', 'desc']
            ]
        ];

        $sources[] = ['heading' => Craft::t('Event Types')];

        foreach ($eventTypes as $eventType) {
            $key = 'eventType:'.$eventType->id;
//            $canEditEvents = craft()->userSession->checkPermission('events-manageEventType:'.$eventType->id);
            $canEditEvents = true;

            $sources[$key] = [
                'label' => $eventType->name,
                'data' => [
                    'handle' => $eventType->handle,
                    'editable' => $canEditEvents
                ],
                'criteria' => [
                    'typeId' => $eventType->id,
                    'editable' => $editable
                ]
            ];
        }

        // Allow plugins to modify the sources
//        craft()->plugins->call('events_modifyEventSources', [
//            &$sources,
//            $context
//        ]);

        return $sources;
    }

    public function defineAvailableTableAttributes()
    {
        $attributes = [
            'title' => ['label' => Craft::t('Title')],
            'type' => ['label' => Craft::t('Type')],
            'slug' => ['label' => Craft::t('Slug')],
            'startDate' => Craft::t('Start Date'),
            'endDate' => Craft::t('End Date'),
        ];

        // Allow plugins to modify the attributes
        $pluginAttributes = craft()->plugins->call('events_defineAdditionalEventTableAttributes', [], true);

        foreach ($pluginAttributes as $thisPluginAttributes) {
            $attributes = array_merge($attributes, $thisPluginAttributes);
        }

        return $attributes;
    }

    public function getDefaultTableAttributes($source = null)
    {
        $attributes = [];

        if ($source == '*') {
            $attributes[] = 'type';
        }

        $attributes[] = 'startDate';
        $attributes[] = 'endDate';

        return $attributes;
    }

    public function defineSearchableAttributes()
    {
        return array('title');
    }

    public function getTableAttributeHtml(BaseElementModel $element, $attribute)
    {
        // First give plugins a chance to set this
        $pluginAttributeHtml = craft()->plugins->callFirst('events_getEventTableAttributeHtml', [
            $element,
            $attribute
        ], true);

        if ($pluginAttributeHtml !== null) {
            return $pluginAttributeHtml;
        }

        $eventType = $element->getEventType();

        switch ($attribute) {
            case 'type': {
                return ($eventType ? Craft::t($eventType->name) : '');
            }
            default: {
                return parent::getTableAttributeHtml($element, $attribute);
            }
        }
    }

    public function defineSortableAttributes()
    {
        $attributes = [
            'title' => Craft::t('Title'),
            'startDate' => Craft::t('Start Date'),
            'endDate' => Craft::t('End Date'),
        ];

        // Allow plugins to modify the attributes
        craft()->plugins->call('events_modifyEventSortableAttributes', [&$attributes]);

        return $attributes;
    }

    public function getStatuses()
    {
        return [
            Events_EventModel::LIVE => Craft::t('Live'),
//            Events_EventModel::PENDING => Craft::t('Pending'),
            Events_EventModel::EXPIRED => Craft::t('Expired'),
            BaseElementModel::DISABLED => Craft::t('Disabled')
        ];
    }

    public function defineCriteriaAttributes()
    {
        return [
            'typeId' => AttributeType::Mixed,
            'type' => AttributeType::Mixed,
            'allDay' => AttributeType::Bool,
            'startDate' => AttributeType::DateTime,
            'endDate' => AttributeType::DateTime,
            'after' => AttributeType::Mixed,
            'order' => [AttributeType::String, 'default' => 'events.startDate asc'],
            'before' => AttributeType::Mixed,
            'status' => [
                AttributeType::String,
                'default' => Events_EventModel::LIVE
            ],
            'editable' => AttributeType::Bool,
        ];
    }

    public function getElementQueryStatusCondition(DbCommand $query, $status)
    {
        $currentTimeDb = DateTimeHelper::currentTimeForDb();

        switch ($status) {
            case Events_EventModel::LIVE: {
                return [
                    'and',
                    'elements.enabled = 1',
                    'elements_i18n.enabled = 1',
                    [
                        'or',
                        [
                            'and',
                            'events.endDate is null',
                            "events.startDate >= '{$currentTimeDb}'",
                        ],
                        [
                            'and',
                            'events.endDate is not null',
                            "events.endDate >= '{$currentTimeDb}'"
                        ]
                    ]
                ];
            }

//            case Commerce_EventModel::PENDING: {
//                return [
//                    'and',
//                    'elements.enabled = 1',
//                    'elements_i18n.enabled = 1',
//                    "events.postDate > '{$currentTimeDb}'"
//                ];
//            }

            case Events_EventModel::EXPIRED: {
                return [
                    'and',
                    'elements.enabled = 1',
                    'elements_i18n.enabled = 1',
                    [
                        'or',
                        [
                            'and',
                            'events.endDate is null',
                            "events.startDate < '{$currentTimeDb}'",
                        ],
                        [
                            'and',
                            'events.endDate is not null',
                            "events.endDate < '{$currentTimeDb}'"
                        ]
                    ]
                ];
            }
        }
    }

    public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
    {
        $query
        ->addSelect('events.*')
        ->join('events_events events', 'events.id = elements.id')
        ->join('events_eventtypes eventtypes', 'eventtypes.id = events.typeId');

        if ($criteria->startDate) {
            $query->andWhere(DbHelper::parseDateParam('events.startDate', $criteria->startDate, $query->params));
        } else {
            if ($criteria->after) {
                $query->andWhere(DbHelper::parseDateParam('events.startDate', '>='.$criteria->after, $query->params));
            }

            if ($criteria->before) {
                $query->andWhere(DbHelper::parseDateParam('events.startDate', '<'.$criteria->before, $query->params));
            }
        }

        if ($criteria->endDate) {
            $query->andWhere(DbHelper::parseDateParam('events.endDate', $criteria->endDate, $query->params));
        }

        if ($criteria->type) {
            if ($criteria->type instanceof Events_EventTypeModel) {
                $criteria->typeId = $criteria->type->id;
                $criteria->type = null;
            } else {
                $query->andWhere(DbHelper::parseParam('eventtypes.handle', $criteria->type, $query->params));
            }
        }

        if ($criteria->typeId) {
            $query->andWhere(DbHelper::parseParam('events.typeId', $criteria->typeId, $query->params));
        }

//        if ($criteria->startDate) {
//            $query->andWhere(DbHelper::parseDateParam('events.startDate', $criteria->startDate, $query->params));
//        }
//
//        if ($criteria->endDate) {
//            $query->andWhere(DbHelper::parseDateParam('events.endDate', $criteria->endDate, $query->params));
//        }

        if ($criteria->allDay) {
            $query->andWhere(DbHelper::parseParam('events.allDay', $criteria->allDay, $query->params));
        }

        if ($criteria->editable) {
            $user = craft()->userSession->getUser();

            if (!$user) {
                return false;
            }

            // Limit the query to only the sections the user has permission to edit
            $editableEventTypeIds = EventsHelper::getEventTypesService()->getEditableEventTypeIds();

            if (!$editableEventTypeIds) {
                return false;
            }

            $query->andWhere([
                'in',
                'events.typeId',
                $editableEventTypeIds
            ]);
        }

        return true;
    }

    public function populateElementModel($row)
    {
        return Events_EventModel::populateModel($row);
    }

    public function getEditorHtml(BaseElementModel $element)
    {
        /** @ var Commerce_EventModel $element */
        $templatesService = craft()->templates;
        $html = $templatesService->renderMacro('events/events/_fields', 'titleField', [$element]);
        $html .= parent::getEditorHtml($element);
//        $html .= $templatesService->renderMacro('events/events/_fields', 'generalFields', [$element]);
//        $html .= $templatesService->renderMacro('events/events/_fields', 'pricingFields', [$element]);
//        $html .= $templatesService->renderMacro('events/events/_fields', 'behavioralMetaFields', [$element]);
        $html .= $templatesService->renderMacro('events/events/_fields', 'generalMetaFields', [$element]);

        return $html;
    }

    /**
     * @inheritdoc BaseElementType::saveElement()
     *
     * @return bool
     */
    public function saveElement(BaseElementModel $element, $params)
    {
        $element->enabled = $params['enabled'] ? $params['enabled'] : null;

        $element->slug = $params['slug'] ? $params['slug'] : $element->slug;

        $startDate = $params['startDate'];
        $endDate = $params['endDate'];

        $element->startDate = $startDate ? DateTime::createFromString($startDate, craft()->timezone) : $element->startDate;

        if (!$element->startDate) {
            $element->startDate = new DateTime();
        }

        $element->endDate = $endDate ? DateTime::createFromString($endDate, craft()->timezone) : null;

        $element->allDay = $params['allDay'] ? $params['allDay'] : null;

        // Route this through Events_EventsService::saveEvent() so the proper entry events get fired.
        return EventsHelper::getEventsService()->saveEvent($element);
    }

    public function routeRequestForMatchedElement(BaseElementModel $element)
    {
        /** @var Events_EventModel $element */
        $status = $element->getStatus();

        if ($status == Events_EventModel::LIVE || $status == BaseElementModel::ENABLED) {
            $eventType = $element->getEventType();

            if ($eventType && $eventType->hasUrls) {
                return [
                    'action' => 'templates/render',
                    'params' => [
                        'template' => $eventType->template,
                        'variables' => [
                            'event' => $element,

                            // Provide the same element as `product` for easy implementation
                            // when using the demo Craft templates - it'll expect a `product` variable
                            'product' => $element,
                        ]
                    ]
                ];
            }
        }

        return false;
    }

    /*public function getEagerLoadingMap($sourceElements, $handle)
    {
        if ($handle == 'isTicketd') {
            $user = craft()->userSession->getUser();
            if ($user)
            {
                // Get the source element IDs
                $sourceElementIds = array();

                foreach ($sourceElements as $sourceElement) {
                    $sourceElementIds[] = $sourceElement->id;
                }

                $map = craft()->db->createCommand()
                    ->select('eventId as source, id as target')
                    ->from('events_tickets')
                    ->where(array('in', 'eventId', $sourceElementIds))
                    ->andWhere('userId = :currentUser', array(':currentUser' => $user->id))
                    ->queryAll();

                return array(
                    'elementType' => 'Events_Ticket',
                    'map' => $map
                );
            }
        }

        return parent::getEagerLoadingMap($sourceElements, $handle);
    }*/
}
