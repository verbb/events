<?php
namespace verbb\events\elements\conditions\purchasedtickets;

use verbb\events\elements\Session;

use Craft;
use craft\base\conditions\BaseElementSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;

class SessionConditionRule extends BaseElementSelectConditionRule implements ElementConditionRuleInterface
{
    // Public Methods
    // =========================================================================

    public function getLabel(): string
    {
        return Craft::t('events', 'Session');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['session', 'sessionId'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->session($this->getElementId());
    }

    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->sessionId);
    }


    // Protected Methods
    // =========================================================================

    protected function elementType(): string
    {
        return Session::class;
    }
}
