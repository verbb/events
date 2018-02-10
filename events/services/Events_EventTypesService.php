<?php
namespace Craft;

class Events_EventTypesService extends BaseApplicationComponent
{

    // Properties
    // =========================================================================

    private $_fetchedAllEventTypes = false;
    private $_eventTypesById;
    private $_allEventTypeIds;
    private $_editableEventTypeIds;


    // Public Methods
    // =========================================================================

    /**
     * @param array $criteria
     *
     * @return Events_EventTypeModel[]
     */
    public function getEventTypes(array $criteria = [])
    {
        $results = Events_EventTypeRecord::model()->findAll($criteria);
        return Events_EventTypeModel::populateModels($results);
    }

    public function getEventTypeLocales($eventTypeId, $indexBy = null)
    {
        $records = Events_EventTypeLocaleRecord::model()->findAllByAttributes([
            'eventTypeId' => $eventTypeId
        ]);

        return Events_EventTypeLocaleModel::populateModels($records, $indexBy);
    }

    public function getAllEventTypes($indexBy = null)
    {
        if (!$this->_fetchedAllEventTypes) {
            $results = Events_EventTypeRecord::model()->findAll();

            if (!isset($this->_eventTypesById)) {
                $this->_eventTypesById = [];
            }

            foreach ($results as $result) {
                $eventType = Events_EventTypeModel::populateModel($result);
                $this->_eventTypesById[$eventType->id] = $eventType;
            }

            $this->_fetchedAllEventTypes = true;
        }

        if ($indexBy == 'id') {
            $eventTypes = $this->_eventTypesById;
        } else if (!$indexBy) {
            $eventTypes = array_values($this->_eventTypesById);
        } else {
            $eventTypes = [];
            foreach ($this->_eventTypesById as $eventType) {
                $eventTypes[$eventType->$indexBy] = $eventType;
            }
        }

        return $eventTypes;
    }

    public function getAllEventTypeIds()
    {
        if (!isset($this->_allEventTypeIds)) {
            $this->_allEventTypeIds = [];

            foreach ($this->getAllEventTypes() as $eventType) {
                $this->_allEventTypeIds[] = $eventType->id;
            }
        }

        return $this->_allEventTypeIds;
    }

    public function getEditableEventTypeIds()
    {
        if (!isset($this->_editableEventTypeIds)) {
            $this->_editableEventTypeIds = [];

            foreach ($this->getAllEventTypeIds() as $eventTypeId) {
                if (craft()->userSession->checkPermission('events-manageEventType:'.$eventTypeId)) {
                    $this->_editableEventTypeIds[] = $eventTypeId;
                }
            }
        }

        return $this->_editableEventTypeIds;
    }

    public function getEditableEventTypes($indexBy = null)
    {
        $editableEventTypeIds = $this->getEditableEventTypeIds();
        $editableEventTypes = [];

        foreach ($this->getAllEventTypes() as $eventTypes) {
            if (in_array($eventTypes->id, $editableEventTypeIds)) {
                if ($indexBy) {
                    $editableEventTypes[$eventTypes->$indexBy] = $eventTypes;
                } else {
                    $editableEventTypes[] = $eventTypes;
                }
            }
        }

        return $editableEventTypes;
    }

    public function saveEventType(Events_EventTypeModel $eventType)
    {
        if ($eventType->id) {
            $eventTypeRecord = Events_EventTypeRecord::model()->findById($eventType->id);

            if (!$eventTypeRecord) {
                throw new Exception(Craft::t('No event type exists with the ID “{id}”', ['id' => $eventType->id]));
            }

            $oldEventType = Events_EventTypeModel::populateModel($eventTypeRecord);
            $isNewEventType = false;
        } else {
            $eventTypeRecord = new Events_EventTypeRecord();
            $isNewEventType = true;
        }

        $eventTypeRecord->name = $eventType->name;
        $eventTypeRecord->handle = $eventType->handle;
        $eventTypeRecord->hasUrls = $eventType->hasUrls;
//        $eventTypeRecord->skuFormat = $eventType->skuFormat;
        $eventTypeRecord->template = $eventType->template;

        // Make sure that all of the URL formats are set properly
        $eventTypeLocales = $eventType->getLocales();

        foreach ($eventTypeLocales as $localeId => $eventTypeLocale) {
            if ($eventType->hasUrls) {
                $urlFormatAttributes = ['urlFormat'];
                $eventTypeLocale->urlFormatIsRequired = true;

                foreach ($urlFormatAttributes as $attribute) {
                    if (!$eventTypeLocale->validate([$attribute])) {
                        $eventType->addError($attribute.'-'.$localeId, $eventTypeLocale->getError($attribute));
                    }
                }
            } else {
                $eventTypeLocale->urlFormat = null;
            }
        }

        $eventTypeRecord->validate();
        $eventType->addErrors($eventTypeRecord->getErrors());

        if (!$eventType->hasErrors()) {
            $transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

            try {
                // Drop the old field layout
                craft()->fields->deleteLayoutById($eventType->fieldLayoutId);

                // Save the new one
                $fieldLayout = $eventType->asa('eventFieldLayout')->getFieldLayout();
                craft()->fields->saveLayout($fieldLayout);
                $eventType->fieldLayoutId = $fieldLayout->id;
                $eventTypeRecord->fieldLayoutId = $fieldLayout->id;

                // Save it!
                $eventTypeRecord->save(false);

                // Now that we have a event type ID, save it on the model
                if (!$eventType->id) {
                    $eventType->id = $eventTypeRecord->id;
                }

                $newLocaleData = [];

                if (!$isNewEventType) {
                    // Get the old event type locales
                    $oldLocaleRecords = Events_EventTypeLocaleRecord::model()->findAllByAttributes([
                        'eventTypeId' => $eventType->id
                    ]);

                    $oldLocales = Events_EventTypeLocaleModel::populateModels($oldLocaleRecords, 'locale');

                    $changedLocaleIds = [];
                }

                foreach ($eventTypeLocales as $localeId => $locale) {
                    // Was this already selected?
                    if (!$isNewEventType && isset($oldLocales[$localeId])) {
                        $oldLocale = $oldLocales[$localeId];

                        // Has the URL format changed?
                        if ($locale->urlFormat != $oldLocale->urlFormat) {
                            craft()->db->createCommand()->update('events_eventtypes_i18n', [
                                'urlFormat' => $locale->urlFormat
                            ], [
                                'id' => $oldLocale->id
                            ]);

                            $changedLocaleIds[] = $localeId;
                        }
                    } else {
                        $newLocaleData[] = [
                            $eventType->id,
                            $localeId,
                            $locale->urlFormat
                        ];
                    }
                }

                // Insert the new locales
                craft()->db->createCommand()->insertAll('events_eventtypes_i18n',
                    ['eventTypeId', 'locale', 'urlFormat'],
                    $newLocaleData
                );

                if (!$isNewEventType) {
                    // Drop any locales that are no longer being used, as well as the associated element
                    // locale rows

                    $droppedLocaleIds = array_diff(array_keys($oldLocales), array_keys($eventTypeLocales));

                    if ($droppedLocaleIds) {
                        craft()->db->createCommand()->delete('events_eventtypes_i18n', [
                            'in',
                            'locale',
                            $droppedLocaleIds
                        ]);
                    }
                }

                if (!$isNewEventType) {
                    // Get all of the event IDs in this group
                    $criteria = craft()->elements->getCriteria('Events_Event');
                    $criteria->typeId = $eventType->id;
                    $criteria->status = null;
                    $criteria->limit = null;
                    $eventIds = $criteria->ids();

                    // Should we be deleting
                    if ($eventIds && $droppedLocaleIds) {
                        craft()->db->createCommand()->delete('elements_i18n', [
                            'and',
                            ['in', 'elementId', $eventIds],
                            ['in', 'locale', $droppedLocaleIds]
                        ]);
                        craft()->db->createCommand()->delete('content', [
                            'and',
                            ['in', 'elementId', $eventIds],
                            ['in', 'locale', $droppedLocaleIds]
                        ]);
                    }
                    
                    // Are there any locales left?
                    if ($eventTypeLocales) {
                        // Drop the old eventType URIs if the event type no longer has URLs
                        if (!$eventType->hasUrls && $oldEventType->hasUrls) {
                            craft()->db->createCommand()->update('elements_i18n',
                                ['uri' => null],
                                ['in', 'elementId', $eventIds]
                            );
                        } else if ($changedLocaleIds) {
                            foreach ($eventIds as $eventId) {
                                craft()->config->maxPowerCaptain();

                                // Loop through each of the changed locales and update all of the events’ slugs and
                                // URIs
                                foreach ($changedLocaleIds as $localeId) {
                                    $criteria = craft()->elements->getCriteria('Events_Event');
                                    $criteria->id = $eventId;
                                    $criteria->locale = $localeId;
                                    $criteria->status = null;
                                    $updateEvent = $criteria->first();

                                    // @todo replace the getContent()->id check with 'strictLocale' param once it's added
                                    if ($updateEvent && $updateEvent->getContent()->id) {
                                        craft()->elements->updateElementSlugAndUri($updateEvent, false, false);
                                    }
                                }
                            }
                        }
                    }
                }

                if ($transaction !== null) {
                    $transaction->commit();
                }
            } catch (\Exception $e) {
                if ($transaction !== null) {
                    $transaction->rollback();
                }

                throw $e;
            }
            return true;
        } else {
            return false;
        }
    }

    public function getEventTypeById($eventTypeId)
    {
        if (!$this->_fetchedAllEventTypes &&
            (!isset($this->_eventTypesById) || !array_key_exists($eventTypeId, $this->_eventTypesById))
        ) {
            $result = Events_EventTypeRecord::model()->findById($eventTypeId);

            if ($result) {
                $eventType = Events_EventTypeModel::populateModel($result);
            } else {
                $eventType = null;
            }

            $this->_eventTypesById[$eventTypeId] = $eventType;
        }

        if (isset($this->_eventTypesById[$eventTypeId])) {
            return $this->_eventTypesById[$eventTypeId];
        }

        return null;
    }

    public function getEventTypeByHandle($handle)
    {
        $result = Events_EventTypeRecord::model()->findByAttributes(['handle' => $handle]);

        if ($result) {
            $eventType = Events_EventTypeModel::populateModel($result);
            $this->_eventTypesById[$eventType->id] = $eventType;

            return $eventType;
        }

        return null;
    }

    public function deleteEventTypeById($id)
    {
        try {
            $transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

            $eventType = $this->getEventTypeById($id);

            $criteria = craft()->elements->getCriteria('Events_Event');
            $criteria->typeId = $eventType->id;
            $criteria->status = null;
            $criteria->limit = null;
            $events = $criteria->find();

            foreach ($events as $event) {
                EventsHelper::getEventsService()->deleteEvent($event);
            }

            $fieldLayoutId = $eventType->asa('eventFieldLayout')->getFieldLayout()->id;
            craft()->fields->deleteLayoutById($fieldLayoutId);

            $eventTypeRecord = Events_EventTypeRecord::model()->findById($eventType->id);
            $affectedRows = $eventTypeRecord->delete();

            if ($transaction !== null) {
                $transaction->commit();
            }

            return (bool)$affectedRows;
        } catch (\Exception $e) {
            if ($transaction !== null) {
                $transaction->rollback();
            }
            throw $e;
        }
    }

    public function isEventTypeTemplateValid(Events_EventTypeModel $eventType)
    {
        if ($eventType->hasUrls) {
            // Set Craft to the site template mode
            $templatesService = craft()->templates;
            $oldTemplateMode = $templatesService->getTemplateMode();
            $templatesService->setTemplateMode(TemplateMode::Site);

            // Does the template exist?
            $templateExists = $templatesService->doesTemplateExist($eventType->template);

            // Restore the original template mode
            $templatesService->setTemplateMode($oldTemplateMode);

            if ($templateExists) {
                return true;
            }
        }

        return false;
    }
    
    public function addLocaleHandler(Event $event)
    {
        /** @var Commerce_OrderModel $order */
        $localeId = $event->params['localeId'];

        // Add this locale to each of the category groups
        $eventTypeLocales = craft()->db->createCommand()
            ->select('eventTypeId, urlFormat')
            ->from('events_eventtypes_i18n')
            ->where('locale = :locale', [':locale' => craft()->i18n->getPrimarySiteLocaleId()])
            ->queryAll();

        if ($eventTypeLocales) {
            $newEventTypeLocales = [];

            foreach ($eventTypeLocales as $eventTypeLocale) {
                $newEventTypeLocales[] = [
                    $eventTypeLocale['eventTypeId'],
                    $localeId,
                    $eventTypeLocale['urlFormat']
                ];
            }

            craft()->db->createCommand()->insertAll('events_eventtypes_i18n', [
                'eventTypeId',
                'locale',
                'urlFormat'
            ], $newEventTypeLocales);
        }

        return true;
    }
}
