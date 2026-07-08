<?php

namespace App\Jobs;

use App\Services\AnalyticsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DailyAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(AnalyticsService $analytics): void
    {
        try {
            $record = $analytics->aggregateDaily();
            Log::info('Daily analytics aggregated', ['date' => $record->date, 'id' => $record->id]);
        } catch (\Exception $e) {
            Log::error('Daily analytics aggregation failed', ['error' => $e->getMessage()]);
        }
    }
}
