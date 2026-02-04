<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanupOldNotifications extends Command
{
    protected $signature = 'notifications:cleanup';
    protected $description = 'Delete old notifications (> 30 days)';

    public function handle(): int
    {
        $cutoff = Carbon::now()->subDays(30);

        $deleted = Notification::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Deleted {$deleted} old notifications.");
        return self::SUCCESS;
    }
}
