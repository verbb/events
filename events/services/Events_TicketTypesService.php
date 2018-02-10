<?php
namespace Craft;

class Events_TicketTypesService extends BaseApplicationComponent
{

    // Properties
    // =========================================================================

    private $_fetchedAllTicketTypes = false;
    private $_ticketTypesById;
    private $_allTicketTypeIds;
    private $_editableTicketTypeIds;


    // Public Methods
    // =========================================================================

    /**
     * @param array $criteria
     *
     * @return Events_TicketTypeModel[]
     */
    public function getTicketTypes(array $criteria = [])
    {
        $results = Events_TicketTypeRecord::model()->findAll($criteria);
        return Events_TicketTypeModel::populateModels($results);
    }

//    public function getTicketTypeLocales($ticketTypeId, $indexBy = null)
//    {
//        $records = Events_TicketTypeLocaleRecord::model()->findAllByAttributes([
//            'ticketTypeId' => $ticketTypeId
//        ]);
//
//        return Events_TicketTypeLocaleModel::populateModels($records, $indexBy);
//    }

    public function getAllTicketTypes($indexBy = null)
    {
        if (!$this->_fetchedAllTicketTypes) {
            $results = Events_TicketTypeRecord::model()->findAll();

            if ($this->_ticketTypesById === null) {
                $this->_ticketTypesById = [];
            }

            foreach ($results as $result) {
                $ticketType = Events_TicketTypeModel::populateModel($result);
                $this->_ticketTypesById[$ticketType->id] = $ticketType;
            }

            $this->_fetchedAllTicketTypes = true;
        }

        if ($indexBy == 'id') {
            $ticketTypes = $this->_ticketTypesById;
        } else if (!$indexBy) {
            $ticketTypes = array_values($this->_ticketTypesById);
        } else {
            $ticketTypes = [];
            foreach ($this->_ticketTypesById as $ticketType) {
                $ticketTypes[$ticketType->$indexBy] = $ticketType;
            }
        }

        return $ticketTypes;
    }

    public function getAllTicketTypeIds()
    {
        if ($this->_allTicketTypeIds === null) {
            $this->_allTicketTypeIds = [];

            foreach ($this->getAllTicketTypes() as $ticketType) {
                $this->_allTicketTypeIds[] = $ticketType->id;
            }
        }

        return $this->_allTicketTypeIds;
    }

    public function getEditableTicketTypeIds()
    {
        if ($this->_editableTicketTypeIds === null) {
            $this->_editableTicketTypeIds = [];

            foreach ($this->getAllTicketTypeIds() as $ticketTypeId) {
                if (craft()->userSession->checkPermission('events-manageTicketType:'.$ticketTypeId)) {
                    $this->_editableTicketTypeIds[] = $ticketTypeId;
                }
            }
        }

        return $this->_editableTicketTypeIds;
    }

    public function getEditableTicketTypes($indexBy = null)
    {
//        $editableTicketTypeIds = $this->getEditableTicketTypeIds();
        $editableTicketTypes = [];

        foreach ($this->getAllTicketTypes() as $ticketTypes) {
            if (in_array($ticketTypes->id, $editableTicketTypes)) {
                if ($indexBy) {
                    $editableTicketTypes[$ticketTypes->$indexBy] = $ticketTypes;
                } else {
                    $editableTicketTypes[] = $ticketTypes;
                }
            }
        }

        return $editableTicketTypes;
    }

    public function saveTicketType(Events_TicketTypeModel $ticketType)
    {
//        if(!craft()->elements->saveElement($ticketType)) {
//            return array('error', $ticketType->getErrors());
//        }

        if ($ticketType->id) {
            $ticketTypeRecord = Events_TicketTypeRecord::model()->findById($ticketType->id);

            if (!$ticketTypeRecord) {
                throw new Exception(Craft::t('No ticket type exists with the ID “{id}”', ['id' => $ticketType->id]));
            }

//            $oldTicketType = Events_EventTypeModel::populateModel($ticketTypeRecord);
            $isNewTicketType = false;
        } else {
            $ticketTypeRecord = new Events_TicketTypeRecord();
            $isNewTicketType = true;
        }

//        $ticketTypeRecord->title = $ticketType->title;
        $ticketTypeRecord->handle = $ticketType->handle;
        $ticketTypeRecord->taxCategoryId = $ticketType->taxCategoryId;
        $ticketTypeRecord->shippingCategoryId = $ticketType->shippingCategoryId;
//        $ticketTypeRecord->hasUrls = $ticketType->hasUrls;
//        $ticketTypeRecord->skuFormat = $ticketType->skuFormat;
//        $ticketTypeRecord->template = $ticketType->template;

        // Make sure that all of the URL formats are set properly
//        $ticketTypeLocales = $ticketType->getLocales();
//
//        foreach ($ticketTypeLocales as $localeId => $ticketTypeLocale) {
//            if ($ticketType->hasUrls) {
//                $urlFormatAttributes = ['urlFormat'];
//                $ticketTypeLocale->urlFormatIsRequired = true;
//
//                foreach ($urlFormatAttributes as $attribute) {
//                    if (!$ticketTypeLocale->validate([$attribute])) {
//                        $ticketType->addError($attribute.'-'.$localeId, $ticketTypeLocale->getError($attribute));
//                    }
//                }
//            } else {
//                $ticketTypeLocale->urlFormat = null;
//            }
//        }

        // Check for title
        if (!$ticketType->getTitle()) {
            $ticketType->addError('title', Craft::t('{attribute} cannot be blank.', ['attribute' => 'Name']));
        }

        $ticketTypeRecord->validate();
        $ticketType->addErrors($ticketTypeRecord->getErrors());

        if (!$ticketType->hasErrors()) {

            // Save the element
            if(!craft()->elements->saveElement($ticketType)) {
                return array('error', $ticketType->getErrors());
            }

            $ticketTypeRecord->id = $ticketType->id;

            // Drop the old field layout
            craft()->fields->deleteLayoutById($ticketType->fieldLayoutId);

            // Save the new one
            $fieldLayout = $ticketType->asa('ticketFieldLayout')->getFieldLayout();
            craft()->fields->saveLayout($fieldLayout);
            $ticketType->fieldLayoutId = $fieldLayout->id;
            $ticketTypeRecord->fieldLayoutId = $fieldLayout->id;

            // Save the record
            $ticketTypeRecord->save(false);

//            // Now that we have a ticket type ID, save it on the model
//            if (!$ticketType->id) {
//                $ticketType->id = $ticketTypeRecord->id;
//            }

//            $transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

//            try {
//                // Drop the old field layout
//                craft()->fields->deleteLayoutById($ticketType->fieldLayoutId);
//
//                // Save the new one
//                $fieldLayout = $ticketType->asa('ticketFieldLayout')->getFieldLayout();
//                craft()->fields->saveLayout($fieldLayout);
//                $ticketType->fieldLayoutId = $fieldLayout->id;
//                $ticketTypeRecord->fieldLayoutId = $fieldLayout->id;
//
//                // Save it!
//                $ticketTypeRecord->save(false);
//
//                // Now that we have a ticket type ID, save it on the model
//                if (!$ticketType->id) {
//                    $ticketType->id = $ticketTypeRecord->id;
//                }
//
//                $newLocaleData = [];
//
//                if (!$isNewTicketType) {
//                    // Get the old ticket type locales
//                    $oldLocaleRecords = Events_TicketTypeLocaleRecord::model()->findAllByAttributes([
//                        'ticketTypeId' => $ticketType->id
//                    ]);
//
//                    $oldLocales = Events_TicketTypeLocaleModel::populateModels($oldLocaleRecords, 'locale');
//
//                    $changedLocaleIds = [];
//                }
//
//                foreach ($ticketTypeLocales as $localeId => $locale) {
//                    // Was this already selected?
//                    if (!$isNewTicketType && isset($oldLocales[$localeId])) {
//                        $oldLocale = $oldLocales[$localeId];
//
//                        // Has the URL format changed?
//                        if ($locale->urlFormat != $oldLocale->urlFormat) {
//                            craft()->db->createCommand()->update('events_tickettypes_i18n', [
//                                'urlFormat' => $locale->urlFormat
//                            ], [
//                                'id' => $oldLocale->id
//                            ]);
//
//                            $changedLocaleIds[] = $localeId;
//                        }
//                    } else {
//                        $newLocaleData[] = [
//                            $ticketType->id,
//                            $localeId,
//                            $locale->urlFormat
//                        ];
//                    }
//                }
//
//                // Insert the new locales
//                craft()->db->createCommand()->insertAll('events_tickettypes_i18n',
//                    ['ticketTypeId', 'locale', 'urlFormat'],
//                    $newLocaleData
//                );
//
//                if (!$isNewTicketType) {
//                    // Drop any locales that are no longer being used, as well as the associated element
//                    // locale rows
//
//                    $droppedLocaleIds = array_diff(array_keys($oldLocales), array_keys($ticketTypeLocales));
//
//                    if ($droppedLocaleIds) {
//                        craft()->db->createCommand()->delete('events_tickettypes_i18n', [
//                            'in',
//                            'locale',
//                            $droppedLocaleIds
//                        ]);
//                    }
//                }

//                if (!$isNewTicketType) {
//                    // Get all of the ticket IDs in this group
//                    $criteria = craft()->elements->getCriteria('Events_Ticket');
//                    $criteria->typeId = $ticketType->id;
//                    $criteria->status = null;
//                    $criteria->limit = null;
//                    $ticketIds = $criteria->ids();
//
//                    // Should we be deleting
//                    if ($ticketIds && $droppedLocaleIds) {
//                        craft()->db->createCommand()->delete('elements_i18n', [
//                            'and',
//                            ['in', 'elementId', $ticketIds],
//                            ['in', 'locale', $droppedLocaleIds]
//                        ]);
//                        craft()->db->createCommand()->delete('content', [
//                            'and',
//                            ['in', 'elementId', $ticketIds],
//                            ['in', 'locale', $droppedLocaleIds]
//                        ]);
//                    }
//
//                    // Are there any locales left?
//                    if ($ticketTypeLocales) {
//                        // Drop the old ticketType URIs if the ticket type no longer has URLs
//                        if (!$ticketType->hasUrls && $oldTicketType->hasUrls) {
//                            craft()->db->createCommand()->update('elements_i18n',
//                                ['uri' => null],
//                                ['in', 'elementId', $ticketIds]
//                            );
//                        } else if ($changedLocaleIds) {
//                            foreach ($ticketIds as $ticketId) {
//                                craft()->config->maxPowerCaptain();
//
//                                // Loop through each of the changed locales and update all of the tickets’ slugs and
//                                // URIs
//                                foreach ($changedLocaleIds as $localeId) {
//                                    $criteria = craft()->elements->getCriteria('Events_Ticket');
//                                    $criteria->id = $ticketId;
//                                    $criteria->locale = $localeId;
//                                    $criteria->status = null;
//                                    $updateTicket = $criteria->first();
//
//                                    // @todo replace the getContent()->id check with 'strictLocale' param once it's added
//                                    if ($updateTicket && $updateTicket->getContent()->id) {
//                                        craft()->elements->updateElementSlugAndUri($updateTicket, false, false);
//                                    }
//                                }
//                            }
//                        }
//                    }
//                }

//                if ($transaction !== null) {
//                    $transaction->commit();
//                }
//            } catch (\Exception $e) {
//                if ($transaction !== null) {
//                    $transaction->rollback();
//                }
//
//                throw $e;
//            }
            return true;
        }

        return false;
    }

    public function getTicketTypeById($ticketTypeId)
    {
        if (!$this->_fetchedAllTicketTypes &&
            ($this->_ticketTypesById === null || !array_key_exists($ticketTypeId, $this->_ticketTypesById))
        ) {
            $result = Events_TicketTypeRecord::model()->findById($ticketTypeId);

            if ($result) {
                $ticketType = Events_TicketTypeModel::populateModel($result);
            } else {
                $ticketType = null;
            }

            $this->_ticketTypesById[$ticketTypeId] = $ticketType;
        }

        if (isset($this->_ticketTypesById[$ticketTypeId])) {
            return $this->_ticketTypesById[$ticketTypeId];
        }

        return null;
    }

    public function getTicketTypeByHandle($handle)
    {
        $result = Events_TicketTypeRecord::model()->findByAttributes(['handle' => $handle]);

        if ($result) {
            $ticketType = Events_TicketTypeModel::populateModel($result);
            $this->_ticketTypesById[$ticketType->id] = $ticketType;

            return $ticketType;
        }

        return null;
    }

    public function deleteTicketTypeById($id)
    {
        try {
            $transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

            $ticketType = $this->getTicketTypeById($id);

//            $criteria = craft()->elements->getCriteria('Events_Ticket');
//            $criteria->typeId = $ticketType->id;
//            $criteria->status = null;
//            $criteria->limit = null;
//            $tickets = $criteria->find();
//
//            foreach ($tickets as $ticket) {
//                EventsHelper::getTicketsService()->deleteTicket($ticket);
//            }

            $fieldLayoutId = $ticketType->asa('ticketFieldLayout')->getFieldLayout()->id;
            craft()->fields->deleteLayoutById($fieldLayoutId);

            $ticketTypeRecord = Events_TicketTypeRecord::model()->findById($ticketType->id);
            $affectedRows = $ticketTypeRecord->delete();

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

//    public function isTicketTypeTemplateValid(Events_TicketTypeModel $ticketType)
//    {
//        if ($ticketType->hasUrls) {
//            // Set Craft to the site template mode
//            $templatesService = craft()->templates;
//            $oldTemplateMode = $templatesService->getTemplateMode();
//            $templatesService->setTemplateMode(TemplateMode::Site);
//
//            // Does the template exist?
//            $templateExists = $templatesService->doesTemplateExist($ticketType->template);
//
//            // Restore the original template mode
//            $templatesService->setTemplateMode($oldTemplateMode);
//
//            if ($templateExists) {
//                return true;
//            }
//        }
//
//        return false;
//    }
    
//    public function addLocaleHandler(Event $event)
//    {
//        /** @var Commerce_OrderModel $order */
//        $localeId = $event->params['localeId'];
//
//        // Add this locale to each of the category groups
//        $ticketTypeLocales = craft()->db->createCommand()
//            ->select('ticketTypeId, urlFormat')
//            ->from('events_tickettypes_i18n')
//            ->where('locale = :locale', [':locale' => craft()->i18n->getPrimarySiteLocaleId()])
//            ->queryAll();
//
//        if ($ticketTypeLocales) {
//            $newTicketTypeLocales = [];
//
//            foreach ($newTicketTypeLocales as $newTicketTypeLocale) {
//                $newEventTypeLocales[] = [
//                    $newTicketTypeLocale['ticketTypeId'],
//                    $localeId,
//                    $newTicketTypeLocale['urlFormat']
//                ];
//            }
//
//            craft()->db->createCommand()->insertAll('events_tickettypes_i18n', [
//                'ticketTypeId',
//                'locale',
//                'urlFormat'
//            ], $newTicketTypeLocales);
//        }
//
//        return true;
//    }
}
