<?php

namespace App\Jobs;

use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;
    public int $maxExceptions = 3;

    public function __construct(
        private mixed $users,
        private string $title,
        private string $message,
        private string $type = 'system',
        private mixed $data = null
    ) {}

    public function handle(): void
    {
        NotificationService::send($this->users, $this->title, $this->message, $this->type, $this->data);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[SendNotificationJob] Failed after retries', [
            'title' => $this->title,
            'type' => $this->type,
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * Calculate exponential backoff time between retries.
     */
    public function backoff(): array
    {
        return [30, 120, 600]; // 30s, 2min, 10min
    }
}
