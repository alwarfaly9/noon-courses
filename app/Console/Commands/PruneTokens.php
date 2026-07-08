<?php

namespace App\Console\Commands;

use App\Models\DeviceToken;
use Illuminate\Console\Command;

class PruneTokens extends Command
{
    protected $signature = 'notifications:prune-tokens {--days=30 : Delete inactive tokens older than this many days}';
    protected $description = 'Remove expired or invalid device tokens older than a given age';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $deleted = DeviceToken::where('is_active', false)
            ->where('updated_at', '<', $cutoff)
            ->delete();

        $this->info("Pruned {$deleted} inactive device token(s) older than {$days} days.");

        return Command::SUCCESS;
    }
}
