<?php
namespace verbb\events\integrations\feedme;

use verbb\events\Events;
use verbb\events\elements\Event as EventElement;
use verbb\events\elements\Ticket as TicketElement;

use Craft;
use craft\helpers\Json;

use craft\feedme\Plugin as FeedMe;
use craft\feedme\base\Element;
use craft\feedme\events\FeedProcessEvent;
use craft\feedme\helpers\DataHelper;
use craft\feedme\services\Process;

use yii\base\Event as YiiEvent;

use Cake\Utility\Hash;
use Carbon\Carbon;
use DateTime;
use Exception;

class Event extends Element
{
    // Properties
    // =========================================================================

    public static $name = 'Event';
    public static $class = EventElement::class;

    public $element;


    // Templates
    // =========================================================================

    public function getGroupsTemplate(): string
    {
        return 'events/_integrations/feedme/groups';
    }

    public function getColumnTemplate(): string
    {
        return 'events/_integrations/feedme/column';
    }

    public function getMappingTemplate(): string
    {
        return 'events/_integrations/feedme/map';
    }


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        // Hook into the process service on each step - we need to re-arrange the feed mapping
        YiiEvent::on(Process::class, Process::EVENT_STEP_BEFORE_PARSE_CONTENT, function(FeedProcessEvent $event) {
            $this->_preParseTickets($event);
        });

        // Hook into the before element save event, because we need to do lots to prepare ticket data
        YiiEvent::on(Process::class, Process::EVENT_STEP_BEFORE_ELEMENT_SAVE, function(FeedProcessEvent $event) {
            $this->_parseTickets($event);
        });
    }

    public function getGroups(): array
    {
        if (Events::$plugin) {
            return Events::$plugin->getEventTypes()->getAllEventTypes();
        }

        return [];
    }

    public function getQuery($settings, $params = [])
    {
        $query = EventElement::find()
            ->anyStatus()
            ->typeId($settings['elementGroup'][EventElement::class])
            ->siteId(Hash::get($settings, 'siteId') ?: Craft::$app->getSites()->getPrimarySite()->id);

        Craft::configure($query, $params);

        return $query;
    }

    public function setModel($settings)
    {
        $this->element = new EventElement();
        $this->element->typeId = $settings['elementGroup'][EventElement::class];

        $siteId = Hash::get($settings, 'siteId');

        if ($siteId) {
            $this->element->siteId = $siteId;
        }

        return $this->element;
    }

    public function save($element, $settings): bool
    {
        $this->beforeSave($element, $settings);

        if (!Craft::$app->getElements()->saveElement($this->element)) {
            $errors = [$this->element->getErrors()];

            if ($this->element->getErrors()) {
                foreach ($this->element->getTickets() as $ticket) {
                    if ($ticket->getErrors()) {
                        $errors[] = $ticket->getErrors();
                    }
                }

                throw new Exception(Json::encode($errors));
            }

            return false;
        }

        return true;
    }


    // Protected Methods
    // =========================================================================

    protected function parsePostDate($feedData, $fieldInfo): DateTime|bool|array|Carbon|string|null
    {
        $value = $this->fetchSimpleValue($feedData, $fieldInfo);
        $formatting = Hash::get($fieldInfo, 'options.match');

        return $this->parseDateAttribute($value, $formatting);
    }

    protected function parseExpiryDate($feedData, $fieldInfo): DateTime|bool|array|Carbon|string|null
    {
        $value = $this->fetchSimpleValue($feedData, $fieldInfo);
        $formatting = Hash::get($fieldInfo, 'options.match');

        return $this->parseDateAttribute($value, $formatting);
    }


    // Private Methods
    // =========================================================================

    private function _preParseTickets($event): void
    {
        $feed = $event->feed;

        // We need to re-arrange the feed-mapping from using ticket-* to putting all these in a
        // tickets[] array for easy management later. If we don't do this, it'll start processing
        // attributes and fields based on the top-level event, which is incorrect..
        foreach ($feed['fieldMapping'] as $fieldHandle => $fieldInfo) {
            if (str_contains($fieldHandle, 'ticket-')) {
                // Add it to tickets[]
                $attribute = str_replace('ticket-', '', $fieldHandle);
                $feed['fieldMapping']['tickets'][$attribute] = $fieldInfo;

                // Remove it from top-level mapping
                unset($feed['fieldMapping'][$fieldHandle]);
            }
        }

        // Save all our changes back to the event model
        $event->feed = $feed;
    }

    private function _parseTickets($event): void
    {
        $feed = $event->feed;
        $feedData = $event->feedData;
        $contentData = $event->contentData;
        $element = $event->element;

        $ticketMapping = Hash::get($feed, 'fieldMapping.tickets');

        // Check to see if there are any tickets at all (there really should be...)
        if (!$ticketMapping) {
            return;
        }

        $ticketData = [];
        $tickets = [];

        // Fetch any existing tickets on the event, indexes by their SKU
        if (isset($element->tickets[0]['sku'])) {
            foreach ($element->tickets as $key => $value) {
                $tickets[$value['sku']] = $value;
            }
        }

        // Weed out any non-ticket mapped field
        $ticketFieldsByNode = [];

        foreach (Hash::flatten($ticketMapping) as $key => $value) {
            if (str_contains($key, 'node') && $value !== 'noimport' && $value !== 'usedefault') {
                $ticketFieldsByNode[] = $value;
            }
        }

        // Now we need to find out how many tickets we're importing - can even be one, and it's all a little tricky...
        foreach ($feedData as $nodePath => $value) {
            foreach ($ticketMapping as $fieldHandle => $fieldInfo) {
                $node = Hash::get($fieldInfo, 'node');

                $feedPath = preg_replace('/(\/\d+\/)/', '/', $nodePath);
                $feedPath = preg_replace('/^(\d+\/)|(\/\d+)/', '', $feedPath);

                if (!in_array($feedPath, $ticketFieldsByNode)) {
                    continue;
                }

                // Try and determine the index. We need to always be dealing with an array of ticket data
                $nodePathSegments = explode('/', $nodePath);
                $ticketIndex = Hash::get($nodePathSegments, 1);

                if (!is_numeric($ticketIndex)) {
                    // Try to check if its only one-level deep (only importing one block type)
                    // which is particularly common for JSON.
                    $ticketIndex = Hash::get($nodePathSegments, 2);

                    if (!is_numeric($ticketIndex)) {
                        $ticketIndex = 0;
                    }
                }

                // Find the node in the feed (stripped of indexes) that matches what's stored in field mapping
                if ($feedPath === $node) {
                    // Store this information, so we can parse the field data later
                    if (!isset($ticketData[$ticketIndex][$fieldHandle])) {
                        $ticketData[$ticketIndex][$fieldHandle] = $fieldInfo;
                    }

                    $ticketData[$ticketIndex][$fieldHandle]['data'][$nodePath] = $value;
                }
            }
        }

        // A separate loop to sort out any defaults we might have (they need to be applied to each ticket)
        // even though the data supplied for them is only provided once.
        foreach ($ticketMapping as $fieldHandle => $fieldInfo) {
            foreach ($ticketData as $ticketNumber => $ticketContent) {
                $node = Hash::get($fieldInfo, 'node');
                $default = Hash::get($fieldInfo, 'default');

                if ($node === 'usedefault') {
                    $ticketData[$ticketNumber][$fieldHandle] = $fieldInfo;
                    $ticketData[$ticketNumber][$fieldHandle]['data'][$fieldHandle] = $default;
                }
            }
        }

        foreach ($ticketData as $ticketNumber => $ticketContent) {
            $attributeData = [];
            $fieldData = [];

            // Parse just the element attributes first. We use these in our field contexts, and need a fully-prepped element
            foreach ($ticketContent as $fieldHandle => $fieldInfo) {
                if (Hash::get($fieldInfo, 'attribute')) {
                    $attributeValue = DataHelper::fetchValue(Hash::get($fieldInfo, 'data'), $fieldInfo);

                    $attributeData[$fieldHandle] = $attributeValue;
                }
            }

            // Create a new ticket, or find an existing one to edit
            if (!isset($tickets[$ticketNumber])) {
                $tickets[$ticketNumber] = new TicketElement();
            }

            $tickets[$ticketNumber]->event = $element;

            // Set the attributes for the element
            $tickets[$ticketNumber]->setAttributes($attributeData, false);

            // Then, do the same for custom fields. Again, this should be done after populating the element attributes
            foreach ($ticketContent as $fieldHandle => $fieldInfo) {
                if (Hash::get($fieldInfo, 'field')) {
                    $data = Hash::get($fieldInfo, 'data');

                    $fieldValue = FeedMe::$plugin->fields->parseField($feed, $element, $data, $fieldHandle, $fieldInfo);

                    if ($fieldValue !== null) {
                        $fieldData[$fieldHandle] = $fieldValue;
                    }
                }
            }

            // Do the same with our custom field data
            $tickets[$ticketNumber]->setFieldValues($fieldData);

            // Add to our contentData variable for debugging
            $contentData['tickets'][] = $attributeData + $fieldData;
        }

        // Set the events tickets
        $element->setTickets($tickets);

        // Save all our changes back to the event model
        $event->feed = $feed;
        $event->feedData = $feedData;
        $event->contentData = $contentData;
        $event->element = $element;
    }

}
