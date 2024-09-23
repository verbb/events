<?php
namespace verbb\events\elements\actions;

use verbb\events\assetbundles\SessionIndexAsset;
use verbb\events\elements\Session;
use verbb\events\models\OccurrenceRange;

use Craft;
use craft\base\ElementAction;
use craft\elements\actions\DeleteActionInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;

use DateTime;

class DeleteSessions extends ElementAction implements DeleteActionInterface
{
    // Properties
    // =========================================================================

    public bool $hard = false;
    public ?string $contentAction = null;
    public array|DateTime|bool|null $contentActionStartDate = null;
    public array|DateTime|bool|null $contentActionEndDate = null;


    // Public Methods
    // =========================================================================

    public function canHardDelete(): bool
    {
        return true;
    }

    public function setHardDelete(): void
    {
        $this->hard = true;
    }

    public function getTriggerLabel(): string
    {
        if ($this->hard) {
            return Craft::t('app', 'Delete permanently');
        }

        return Craft::t('app', 'Delete…');
    }

    public static function isDestructive(): bool
    {
        return true;
    }

    public function getTriggerHtml(): ?string
    {
        if ($this->hard) {
            return '<div class="btn formsubmit">' . $this->getTriggerLabel() . '</div>';
        }

        $view = Craft::$app->getView();

        $view->registerAssetBundle(SessionIndexAsset::class);

        $view->registerJsWithVars(fn($type) => <<<JS
            (() => {
                const trigger = new Craft.ElementActionTrigger({
                    type: $type,
                    bulk: true,
                    activate: (selectedItems, elementIndex) => {
                        const ids = [];

                        for (let i = 0; i < selectedItems.length; i++) {
                            const element = selectedItems.eq(i).find('.element');

                            if (Garnish.hasAttr(element, 'data-recurring')) {
                                ids.push(element.attr('data-id'));
                            }
                        }

                        if (ids.length) {
                            const modal = new Craft.Events.DeleteSessionModal(ids, {
                                onSubmit: () => {
                                    elementIndex.submitAction($type, Garnish.getPostData(modal.\$container));
                                    modal.hide();
                                    return false;
                                },
                            });
                        } else {
                            elementIndex.submitAction(trigger.\$trigger.data('action'), Garnish.getPostData(trigger.\$trigger));
                        }
                    },
                });
            })();
        JS, [static::class]);

        return null;
    }

    public function getConfirmationMessage(): ?string
    {
        if ($this->hard) {
            return Craft::t('app', 'Are you sure you want to permanently delete the selected {type}?', [
                'type' => Session::pluralLowerDisplayName(),
            ]);
        }

        return null;
    }

    public function performAction(ElementQueryInterface $query): bool
    {
        $sessions = $query->all();
        $sessionIds = ArrayHelper::getColumn($sessions, 'id');

        // If this is a recurring session, get any additional sessions based on the occurrence
        if ($this->contentAction) {
            $selectedSessions = $sessions;

            // Special-case for custom occurence, we just use the range, ditching any we've selected
            if ($this->contentAction === 'custom') {
                $sessions = [];
                $sessionIds = [];
            }

            foreach ($selectedSessions as $selectedSession) {
                if ($selectedSession->getIsRecurring()) {
                    $occurenceRange = new OccurrenceRange([
                        'type' => $this->contentAction,
                        'startDate' => $this->contentActionStartDate,
                        'endDate' => $this->contentActionEndDate,
                    ]);

                    foreach ($occurenceRange->getSessions($selectedSession) as $extraSession) {
                        if (!in_array($extraSession->id, $sessionIds)) {
                            $sessions[] = $extraSession;
                        }
                    }
                }
            }
        }

        $elementsService = Craft::$app->getElements();
        $deletedCount = 0;

        foreach ($sessions as $session) {
            if ($elementsService->deleteElement($session, $this->hard)) {
                $deletedCount++;
            }
        }

        if ($deletedCount !== count($sessions)) {
            if ($deletedCount === 0) {
                $this->setMessage(Craft::t('app', 'Couldn’t delete {type}.', [
                    'type' => Session::pluralLowerDisplayName(),
                ]));
            } else {
                $this->setMessage(Craft::t('app', 'Couldn’t delete all {type}.', [
                    'type' => Session::pluralLowerDisplayName(),
                ]));
            }

            return false;
        }

        $this->setMessage(Craft::t('app', '{type} deleted.', [
            'type' => Session::pluralDisplayName(),
        ]));

        return true;
    }
}
