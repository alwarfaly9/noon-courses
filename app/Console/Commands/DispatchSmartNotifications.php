<?php

namespace App\Console\Commands;

use App\Services\NotificationRulesService;
use Illuminate\Console\Command;

class DispatchSmartNotifications extends Command
{
    protected $signature   = 'notifications:dispatch {--user= : Process a single user ID for testing}';
    protected $description = 'Run the behavioral notification rules engine and send smart push notifications';

    public function handle(NotificationRulesService $service): int
    {
        $userId = $this->option('user');

        if ($userId) {
            $user = \App\Models\User::find($userId);
            if (!$user) {
                $this->error("User {$userId} not found.");
                return Command::FAILURE;
            }
            $service->processUser($user);
            $this->info("Processed user {$userId}.");
            return Command::SUCCESS;
        }

        $this->info('Dispatching smart notifications…');
        $start = now();
        $service->dispatchAll();
        $elapsed = $start->diffInSeconds(now());
        $this->info("Done in {$elapsed}s.");

        return Command::SUCCESS;
    }
}
