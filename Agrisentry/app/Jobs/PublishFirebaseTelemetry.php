<?php

namespace App\Jobs;

use App\Models\Collar;
use App\Services\FirebaseTelemetry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PublishFirebaseTelemetry implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;
    public int $timeout = 30;

    public function __construct(public readonly int $collarId) {}

    public function backoff(): array
    {
        return [10, 30, 120, 300];
    }

    public function handle(FirebaseTelemetry $firebase): void
    {
        // Load current state so delayed retries do not replay an old reading.
        $collar = Collar::with('goat')->find($this->collarId);
        if ($collar) $firebase->publish($collar);
    }
}
