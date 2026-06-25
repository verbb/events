<?php
namespace verbb\events\services;

use verbb\events\elements\Event;
use verbb\events\jobs\UpdateEventTickets;
use verbb\events\models\EventTicketUpdate;
use verbb\events\records\EventTicketUpdate as EventTicketUpdateRecord;

use Craft;
use craft\helpers\Db;
use craft\helpers\Queue;
use craft\helpers\UrlHelper;

use yii\base\Component;

class EventTicketUpdates extends Component
{
    // Public Methods
    // =========================================================================

    public function queueUpdate(Event $event): EventTicketUpdate
    {
        $activeUpdate = $this->getActiveUpdate((int)$event->id);

        if ($activeUpdate) {
            return $activeUpdate;
        }

        $record = new EventTicketUpdateRecord();
        $record->eventId = (int)$event->id;
        $record->status = EventTicketUpdate::STATUS_QUEUED;
        $record->progress = 0;
        $record->description = Craft::t('events', 'Queued ticket updates…');
        $record->save(false);

        $update = $this->_hydrate($record);

        Queue::push(new UpdateEventTickets([
            'eventId' => (int)$event->id,
            'updateId' => (int)$update->id,
        ]));

        return $update;
    }

    public function getUpdateById(int $id): ?EventTicketUpdate
    {
        $record = EventTicketUpdateRecord::findOne($id);

        return $record ? $this->_hydrate($record) : null;
    }

    public function getActiveUpdate(int $eventId): ?EventTicketUpdate
    {
        $record = EventTicketUpdateRecord::find()
            ->where([
                'eventId' => $eventId,
                'status' => [EventTicketUpdate::STATUS_QUEUED, EventTicketUpdate::STATUS_RUNNING],
            ])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        return $record ? $this->_hydrate($record) : null;
    }

    public function getLatestFailedUpdate(int $eventId): ?EventTicketUpdate
    {
        $record = EventTicketUpdateRecord::find()
            ->where([
                'eventId' => $eventId,
                'status' => EventTicketUpdate::STATUS_FAILED,
            ])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        return $record ? $this->_hydrate($record) : null;
    }

    public function getEventStatusPayload(Event $event): array
    {
        $hasPendingChanges = $event->hasPendingTicketChanges();
        $activeUpdate = $this->getActiveUpdate((int)$event->id);

        if ($activeUpdate) {
            return array_merge($this->getStatusPayload($activeUpdate), [
                'state' => $activeUpdate->status,
                'hasPendingChanges' => $hasPendingChanges,
            ]);
        }

        if ($hasPendingChanges) {
            $failedUpdate = $this->getLatestFailedUpdate((int)$event->id);

            if ($failedUpdate) {
                return array_merge($this->getStatusPayload($failedUpdate), [
                    'state' => EventTicketUpdate::STATUS_FAILED,
                    'hasPendingChanges' => true,
                ]);
            }

            return [
                'state' => 'pending',
                'hasPendingChanges' => true,
                'progress' => 0,
                'description' => null,
                'error' => null,
                'uid' => null,
            ];
        }

        return [
            'state' => EventTicketUpdate::STATUS_COMPLETE,
            'hasPendingChanges' => false,
            'progress' => 1,
            'description' => null,
            'error' => null,
            'uid' => null,
        ];
    }

    public function getStatusPayload(EventTicketUpdate $update): array
    {
        return [
            'uid' => $update->uid,
            'state' => $update->status,
            'progress' => $update->progress,
            'description' => $update->description,
            'error' => $update->error,
        ];
    }

    public function getStatusUrl(int $eventId): string
    {
        return UrlHelper::actionUrl('events/events/ticket-update-status', [
            'eventId' => $eventId,
        ]);
    }

    public function markRunning(EventTicketUpdate $update): void
    {
        Db::update('{{%events_ticket_updates}}', [
            'status' => EventTicketUpdate::STATUS_RUNNING,
            'progress' => max($update->progress, 0.05),
            'description' => Craft::t('events', 'Updating tickets…'),
            'dateUpdated' => Db::prepareDateForDb(new \DateTime()),
        ], ['id' => $update->id]);

        $update->status = EventTicketUpdate::STATUS_RUNNING;
    }

    public function updateProgress(EventTicketUpdate $update, float $progress, string $description): void
    {
        Db::update('{{%events_ticket_updates}}', [
            'progress' => $progress,
            'description' => $description,
            'dateUpdated' => Db::prepareDateForDb(new \DateTime()),
        ], ['id' => $update->id]);

        $update->progress = $progress;
        $update->description = $description;
    }

    public function markComplete(EventTicketUpdate $update): void
    {
        Db::update('{{%events_ticket_updates}}', [
            'status' => EventTicketUpdate::STATUS_COMPLETE,
            'progress' => 1,
            'description' => Craft::t('events', 'Ticket updates complete.'),
            'error' => null,
            'dateUpdated' => Db::prepareDateForDb(new \DateTime()),
        ], ['id' => $update->id]);

        $update->status = EventTicketUpdate::STATUS_COMPLETE;
        $update->progress = 1;
    }

    public function markFailed(EventTicketUpdate $update, string $error): void
    {
        Db::update('{{%events_ticket_updates}}', [
            'status' => EventTicketUpdate::STATUS_FAILED,
            'error' => $error,
            'dateUpdated' => Db::prepareDateForDb(new \DateTime()),
        ], ['id' => $update->id]);

        $update->status = EventTicketUpdate::STATUS_FAILED;
        $update->error = $error;
    }


    // Private Methods
    // =========================================================================

    private function _hydrate(EventTicketUpdateRecord $record): EventTicketUpdate
    {
        return new EventTicketUpdate([
            'id' => (int)$record->id,
            'eventId' => (int)$record->eventId,
            'status' => $record->status,
            'progress' => (float)$record->progress,
            'description' => $record->description,
            'error' => $record->error,
            'uid' => $record->uid,
        ]);
    }
}
