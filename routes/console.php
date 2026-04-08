<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\SupportTicket;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('support-tickets:auto-close', function () {
    $cutoff = now()->subDays(30);

    $closedCount = SupportTicket::query()
        ->where('status', SupportTicket::STATUS_OPEN)
        ->where(function ($query) use ($cutoff) {
            $query
                ->where('last_message_at', '<=', $cutoff)
                ->orWhere(function ($nestedQuery) use ($cutoff) {
                    $nestedQuery
                        ->whereNull('last_message_at')
                        ->where('created_at', '<=', $cutoff);
                });
        })
        ->update([
            'status' => SupportTicket::STATUS_CLOSED,
            'closed_at' => now(),
            'closed_by_type' => 'system',
            'closed_by_id' => null,
            'updated_at' => now(),
        ]);

    $this->info("Auto-closed {$closedCount} support ticket(s).");
})->purpose('Automatically close support tickets inactive for 30 days');

Schedule::command('support-tickets:auto-close')->dailyAt('01:00');
