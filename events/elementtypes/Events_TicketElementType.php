<?php
namespace Craft;

class Events_TicketElementType extends BaseElementType
{

    // Public Methods
    // =========================================================================

    public function getName()
    {
        return Craft::t('Ticket');
    }

    public function hasContent()
    {
        return true;
    }

    public function hasStatuses()
    {
        return true;
    }

    public function getSources($context = null)
    {
        $eventTypes = EventsHelper::getEventTypesService()->getEventTypes();

        $eventTypeIds = [];

        foreach ($eventTypes as $eventType) {
            $eventTypeIds[] = $eventType->id;
        }

        $sources = [
            '*' => [
                'label' => Craft::t('All event types'),
                'criteria' => ['ticketIssueDate' => $eventTypeIds],
                'defaultSort' => ['dateCreated', 'desc']
            ]
        ];

        $sources[] = ['heading' => Craft::t('Event Types')];

        foreach ($eventTypes as $eventType) {
            $key = 'eventType:'.$eventType->id;

            $sources[$key] = [
                'label' => $eventType->name,
                'data' => [
                    'handle' => $eventType->handle
                ],
                'criteria' => ['typeId' => $eventType->id]
            ];
        }

        // Allow plugins to modify the sources
        craft()->plugins->call('events_modifyTicketSources', [
            &$sources,
            $context
        ]);

        return $sources;
    }

    public function defineAvailableTableAttributes()
    {
        $attributes = [
            'event' => ['label' => Craft::t('Ticketd Event')],
            'eventType' => ['label' => Craft::t('Event Type')],
            'dateCreated' => ['label' => Craft::t('Ticket Issue Date')],
//            'ticketdTo' => ['label' => Craft::t('Ticketd To')],
            'orderLink' => ['label' => Craft::t('Associated Order')]
        ];

        // Allow plugins to modify the attributes
        $pluginAttributes = craft()->plugins->call('events_defineAdditionalTicketTableAttributes', [], true);

        foreach ($pluginAttributes as $thisPluginAttributes) {
            $attributes = array_merge($attributes, $thisPluginAttributes);
        }

        return $attributes;
    }

    public function getDefaultTableAttributes($source = null)
    {
        $attributes = [];

        if ($source == '*') {
            $attributes[] = 'eventType';
        }

        $attributes[] = 'event';
        $attributes[] = 'dateCreated';
//        $attributes[] = 'ticketdTo';
        $attributes[] = 'orderLink';


        return $attributes;
    }

    public function defineSearchableAttributes()
    {
//        return ['ticketdTo', 'event'];
        return ['event'];
    }

    public function getTableAttributeHtml(BaseElementModel $element, $attribute)
    {
        // First give plugins a chance to set this
        $pluginAttributeHtml = craft()->plugins->callFirst('events_getTicketTableAttributeHtml', [
            $element,
            $attribute
        ], true);

        if ($pluginAttributeHtml !== null) {
            return $pluginAttributeHtml;
        }

        /**
         * @var Events_TicketModel $element
         */
        switch ($attribute) {
            case 'eventType': {
                return $element->getEventType();
            }

//            case 'ticketdTo': {
//                return $element->getTicketdTo();
//            }

            case 'orderLink': {
                $url = $element->getOrderEditUrl();

                return $url ? '<a href="'.$url.'">'.Craft::t('View order').'</a>' : '';
            }

            default: {
                return parent::getTableAttributeHtml($element, $attribute);
            }
        }
    }

    public function defineSortableAttributes()
    {
        $attributes = [
            'slug' => Craft::t('Event name'),
//            'ticketdTo' => Craft::t('Owner'),
//            'ticketDate' => Craft::t('Ticket date'),
        ];

        // Allow plugins to modify the attributes
        craft()->plugins->call('events_modifyTicketSortableAttributes', [&$attributes]);

        return $attributes;
    }

    public function defineCriteriaAttributes()
    {
        return [
//            'email' => AttributeType::String,
//            'ownerEmail' => AttributeType::String,
//            'userEmail' => AttributeType::String,
//
//            'owner' => AttributeType::Mixed,
//            'ownerId' => AttributeType::Number,

            'event' => AttributeType::Mixed,
            'eventId' => AttributeType::Number,

            'type' => AttributeType::Mixed,
            'typeId' => AttributeType::Number,

            'ticketType' => AttributeType::Mixed,
            'ticketTypeId' => AttributeType::Number,

//            'ticketDate' => AttributeType::DateTime,
//            'before' => AttributeType::Bool,
//            'after' => AttributeType::Bool,

//            'orderId' => AttributeType::Number,
//            'ticketKey' => AttributeType::String,

//            'status' => [
//                AttributeType::String,
//                'default' => Events_EventModel::LIVE
//            ],
//
            'order' => [AttributeType::String, 'default' => 'tickets.dateCreated ASC'],
        ];
    }

    public function getElementQueryStatusCondition(DbCommand $query, $status)
    {
        switch ($status) {
            case BaseElementModel::ENABLED: {
                return 'elements.enabled = 1';
            }

            case BaseElementModel::DISABLED: {
                return 'elements.enabled = 0';
            }
        }
    }

    public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
    {
        $query
//            ->addSelect("tickets.id, tickets.eventId, tickets.ticketKey, tickets.ownerName, tickets.ownerEmail, tickets.userId, tickets.orderId, events.typeId as eventTypeId")
//            ->addSelect("tickets.id, tickets.eventId, events.typeId as eventTypeId, tickets.ticketTypeId, tickets.price, tickets.quantity")
            ->addSelect("tickets.*, events.typeId as eventTypeId")
            ->join('events_tickets tickets', 'tickets.id = elements.id')
            ->leftJoin('events_events events', 'events.id = tickets.eventId')
//            ->leftJoin('users users', 'users.id = tickets.userId')
            ->leftJoin('events_eventtypes eventtypes', 'eventtypes.id = events.typeId');

//        if ($criteria->email) {
//            $query->andWhere([
//                'or',
//                ['tickets.ownerEmail = :email', 'users.email = :email'],
//                [':email' => $criteria->ticketdEmail]
//            ]);
//        }

//        if ($criteria->ownerEmail) {
//            $query->andWhere(DbHelper::parseParam('tickets.ownerEmail', $criteria->ownerEmail, $query->params));
//        }
//
//        if ($criteria->userEmail) {
//            $query->andWhere(DbHelper::parseParam('users.email', $criteria->userEmail, $query->params));
//        }
//
//        if ($criteria->owner) {
//            if ($criteria->owner instanceof UserModel) {
//                $criteria->ownerId = $criteria->owner->id;
//                $criteria->owner = null;
//            } else {
//                $query->andWhere(DbHelper::parseParam('users.username', $criteria->owner, $query->params));
//            }
//        }
//
//        if ($criteria->ownerId) {
//            $query->andWhere(DbHelper::parseParam('tickets.userId', $criteria->ownerId, $query->params));
//        }

        if ($criteria->event) {
            if ($criteria->event instanceof Events_EventModel) {
                $criteria->eventId = $criteria->event->id;
                $criteria->event = null;
            } else {
                $query->andWhere(DbHelper::parseParam('events.sku', $criteria->type, $query->params));
            }
        }

        if ($criteria->eventId) {
//            Craft::dd($criteria->eventId);
//            if (!$criteria->eventId == ':all:') {
                $query->andWhere(DbHelper::parseParam('events.id', $criteria->eventId, $query->params));
//            }
//        } else {
//            $query->andWhere(DbHelper::parseParam('tickets.eventId', ':notempty:', $query->params));
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

//        if ($criteria->ticketDate) {
//            $query->andWhere(DbHelper::parseDateParam('tickets.dateCreated', $criteria->ticketDate, $query->params));
//        } else {
//            if ($criteria->after) {
//                $query->andWhere(DbHelper::parseDateParam('tickets.dateCreated', '>='.$criteria->after, $query->params));
//            }
//
//            if ($criteria->before) {
//                $query->andWhere(DbHelper::parseDateParam('tickets.dateCreated', '<'.$criteria->before, $query->params));
//            }
//        }

//        if ($criteria->orderId) {
//            $query->andWhere(DbHelper::parseParam('tickets.orderId', $criteria->orderId, $query->params));
//        }
//
//        if ($criteria->ticketKey) {
//            $query->andWhere(DbHelper::parseParam('tickets.ticketKey', $criteria->ticketKey, $query->params));
//        }

        if (isset($criteria->availableFrom)) {
            $query->andWhere([
                'or',
                'tickets.availableFrom is null',
                DbHelper::parseDateParam('tickets.availableFrom', '<='.$criteria->availableFrom, $query->params),
            ]);
        }

        if (isset($criteria->availableTo)) {
            $query->andWhere([
                'or',
                'tickets.availableTo is null',
                DbHelper::parseDateParam('tickets.availableTo', '>='.$criteria->availableTo, $query->params),
            ]);
        }

        return true;
    }

    public function populateElementModel($row)
    {
        return Events_TicketModel::populateModel($row);
    }

    public function getEagerLoadingMap($sourceElements, $handle)
    {
        if ($handle == 'event') {
            // Get the source element IDs
            $sourceElementIds = array();

            foreach ($sourceElements as $sourceElement) {
                $sourceElementIds[] = $sourceElement->id;
            }

            $map = craft()->db->createCommand()
                ->select('id as source, eventId as target')
                ->from('events_tickets')
                ->where(array('in', 'id', $sourceElementIds))
                ->queryAll();

            return array(
                'elementType' => 'Events_Event',
                'map' => $map
            );
        }

        if ($handle == 'order') {
            // Get the source element IDs
            $sourceElementIds = array();

            foreach ($sourceElements as $sourceElement) {
                $sourceElementIds[] = $sourceElement->id;
            }

            $map = craft()->db->createCommand()
                ->select('id as source, orderId as target')
                ->from('events_tickets')
                ->where(array('in', 'id', $sourceElementIds))
                ->queryAll();

            return array(
                'elementType' => 'Commerce_Order',
                'map' => $map
            );
        }

        if ($handle == 'owner') {
            // Get the source element IDs
            $sourceElementIds = array();

            foreach ($sourceElements as $sourceElement) {
                $sourceElementIds[] = $sourceElement->id;
            }

            $map = craft()->db->createCommand()
                ->select('id as source, userId as target')
                ->from('events_tickets')
                ->where(array('in', 'id', $sourceElementIds))
                ->queryAll();

            return array(
                'elementType' => 'User',
                'map' => $map
            );
        }

        return parent::getEagerLoadingMap($sourceElements, $handle);
    }


    // Protected methods
    // =========================================================================

    protected function prepElementCriteriaForTableAttribute(ElementCriteriaModel $criteria, $attribute)
    {
        if ($attribute == 'event') {
            $with = $criteria->with ?: array();
            $with[] = 'event';
            $criteria->with = $with;
            return;
        }

//        if ($attribute == 'ticketdTo') {
//            $with = $criteria->with ?: array();
//            $with[] = 'owner';
//            $criteria->with = $with;
//            return;
//        }

        parent::prepElementCriteriaForTableAttribute($criteria, $attribute);
    }

}
