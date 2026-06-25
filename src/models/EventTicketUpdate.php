<?php
namespace verbb\events\models;

use craft\base\Model;

class EventTicketUpdate extends Model
{
    // Constants
    // =========================================================================

    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETE = 'complete';
    public const STATUS_FAILED = 'failed';


    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?int $eventId = null;
    public string $status = self::STATUS_QUEUED;
    public float $progress = 0;
    public ?string $description = null;
    public ?string $error = null;
    public ?string $uid = null;


    // Public Methods
    // =========================================================================

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_QUEUED, self::STATUS_RUNNING], true);
    }
}
