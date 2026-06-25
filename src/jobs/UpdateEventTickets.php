<?php
namespace verbb\events\jobs;

use verbb\events\elements\Event;
use verbb\events\Events;
use verbb\events\models\EventTicketUpdate;

use Craft;
use craft\queue\BaseJob;

use Throwable;

class UpdateEventTickets extends BaseJob
{
    // Properties
    // =========================================================================

    public ?int $eventId = null;
    public ?int $updateId = null;


    // Public Methods
    // =========================================================================

    public function execute($queue): void
    {
        $service = Events::$plugin->getEventTicketUpdates();
        $update = $service->getUpdateById((int)$this->updateId);
        $event = Event::find()->id((int)$this->eventId)->status(null)->one();

        if (!$update || !$event) {
            return;
        }

        $service->markRunning($update);
        $this->setProgress($queue, 0.05, Craft::t('events', 'Preparing ticket updates…'));

        try {
            $event->updateTickets(function(float $progress, string $description) use ($queue, $service, $update): void {
                $service->updateProgress($update, $progress, $description);
                $this->setProgress($queue, $progress, $description);
            });

            $service->markComplete($update);
            $this->setProgress($queue, 1, Craft::t('events', 'Ticket updates complete.'));
        } catch (Throwable $e) {
            $service->markFailed($update, $e->getMessage());

            Events::error('Ticket update failed for event {eventId}: {message}', [
                'eventId' => $this->eventId,
                'message' => $e->getMessage(),
            ], false);

            throw $e;
        }
    }


    // Protected Methods
    // =========================================================================

    protected function defaultDescription(): ?string
    {
        return Craft::t('events', 'Updating event tickets');
    }
}
