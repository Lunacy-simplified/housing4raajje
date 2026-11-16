<?php

use App\Models\Lease;
use App\Models\SystemParameter;
use App\Notifications\Notice;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
 * Daily lease housekeeping (FR-2 and the lease timeline):
 *  - leases past their end date become "expired" and the property is available again;
 *  - leases ending in exactly N days get a renewal reminder (N = the renewal_reminder_days setting).
 * Run it by hand with: php artisan leases:daily
 */
Artisan::command('leases:daily', function () {
    $expired = Lease::where('status', 'active')->whereDate('end_date', '<', today())->with('property', 'client', 'owner')->get();

    foreach ($expired as $lease) {
        $lease->status = 'expired';
        $lease->save();
        $lease->property->forceFill(['availability_status' => 'available'])->save();
        Notice::to([$lease->client, $lease->owner], "Lease {$lease->reference} has ended", 'The property is available again.', route('leases.show', $lease));
    }

    $days = (int) SystemParameter::setting('renewal_reminder_days', 30);
    $ending = Lease::where('status', 'active')->whereDate('end_date', today()->addDays($days))->with('client', 'owner')->get();

    foreach ($ending as $lease) {
        Notice::to(
            [$lease->client, $lease->owner],
            "Lease {$lease->reference} ends in {$days} days",
            "It ends on {$lease->end_date->format('j M Y')}. Contact the branch to renew.",
            route('leases.show', $lease)
        );
    }

    $this->info("Expired: {$expired->count()}. Renewal reminders: {$ending->count()}.");
})->purpose('Expire finished leases and send renewal reminders');

Schedule::command('leases:daily')->dailyAt('08:00');
