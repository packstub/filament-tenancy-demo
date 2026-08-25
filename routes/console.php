<?php

use Illuminate\Support\Facades\Schedule;

// Hosted demo: wipe visitor changes and re-seed every hour.
// Set DEMO_RESET_SCHEDULE=false (default) anywhere you don't want this.
if (env('DEMO_RESET_SCHEDULE', false)) {
    Schedule::command('demo:reset')->hourly()->withoutOverlapping();
}
