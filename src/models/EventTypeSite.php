<?php
namespace verbb\events\models;

use verbb\events\Events;

use Craft;
use craft\base\Model;
use craft\models\Site;

use yii\base\InvalidConfigException;

class EventTypeSite extends Model
{
    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?int $eventTypeId = null;
    public ?int $siteId = null;
    public ?bool $hasUrls = null;
    public ?string $uriFormat = null;
    public ?string $template = null;
    public bool $enabledByDefault = true;
    public bool $uriFormatIsRequired = true;

    private ?EventType $_eventType = null;
    private ?Site $_site = null;


    // Public Methods
    // =========================================================================

    public function getEventType(): EventType
    {
        if ($this->_eventType !== null) {
            return $this->_eventType;
        }

        if (!$this->eventTypeId) {
            throw new InvalidConfigException('Site is missing its event type ID');
        }

        if (($this->_eventType = Events::$plugin->getEventTypes()->getEventTypeById($this->eventTypeId)) === null) {
            throw new InvalidConfigException('Invalid event type ID: ' . $this->eventTypeId);
        }

        return $this->_eventType;
    }

    public function setEventType(EventType $eventType): void
    {
        $this->_eventType = $eventType;
    }

    public function getSite(): Site
    {
        if ($this->_site !== null) {
            return $this->_site;
        }

        if (!$this->siteId) {
            throw new InvalidConfigException('Event type site is missing its site ID');
        }

        if (($this->_site = Craft::$app->getSites()->getSiteById($this->siteId)) === null) {
            throw new InvalidConfigException('Invalid site ID: ' . $this->siteId);
        }

        return $this->_site;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        if ($this->uriFormatIsRequired) {
            $rules[] = ['uriFormat', 'required'];
        }

        return $rules;
    }
}
