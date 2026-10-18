<?php
namespace verbb\events\helpers;

use verbb\events\elements\Event as EventElement;

use Craft;
use craft\controllers\NestedElementsController;

use yii\base\ActionEvent;
use yii\base\Event;
use yii\web\ForbiddenHttpException;

class TicketTypePermissions
{
    // Static Methods
    // =========================================================================

    public static function registerRequestHandlers(): void
    {
        Event::on(NestedElementsController::class, NestedElementsController::EVENT_BEFORE_ACTION, [self::class, 'authorizeReorder']);
    }

    public static function canReorder(EventElement $owner): bool
    {
        $user = Craft::$app->getUser()->getIdentity();
        $elements = Craft::$app->getElements();

        if (!$user || $owner->getIsRevision() || !$elements->canView($owner, $user)) {
            return false;
        }

        // Match Craft's editor permission check for provisional drafts.
        $editableOwner = $owner->isProvisionalDraft ? $owner->getCanonical(true) : $owner;

        if (!$elements->canSave($editableOwner, $user)) {
            return false;
        }

        // Moving one item can change every sibling's position, including disabled items.
        foreach ($owner->getTicketTypes(true) as $ticketType) {
            if (!$elements->canView($ticketType, $user) || !$elements->canSave($ticketType, $user)) {
                return false;
            }
        }

        return true;
    }

    public static function authorizeReorder(ActionEvent $event): void
    {
        if ($event->action->id !== 'reorder') {
            return;
        }

        $request = $event->sender->request;
        $attribute = $request->getBodyParam('attribute');
        $ownerType = $request->getBodyParam('ownerElementType');

        // PHP getter names are case-insensitive; both public ownership aliases need the same guard.
        if (!is_string($attribute) || !in_array(strtolower($attribute), ['tickettypes', 'alltickettypes'], true) || !is_string($ownerType) || !is_a($ownerType, EventElement::class, true)) {
            return;
        }

        $owner = Craft::$app->getElements()->getElementById(
            $request->getRequiredBodyParam('ownerId'),
            $ownerType,
            $request->getRequiredBodyParam('ownerSiteId'),
        );

        // Recheck current permissions even when a browser session retains an older authorization.
        if (!$owner instanceof EventElement || !self::canReorder($owner)) {
            throw new ForbiddenHttpException('User is not authorized to reorder these ticket types.');
        }

        $ids = array_map(fn($id) => (int)$id, $request->getRequiredBodyParam('elementIds'));
        $allowedIds = $owner->getTicketTypes(true)->map(fn($ticketType) => $ticketType->id)->all();

        if (array_diff($ids, $allowedIds)) {
            throw new ForbiddenHttpException('Ticket types must belong to the event being reordered.');
        }
    }
}
