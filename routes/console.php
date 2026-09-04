<?php

use Illuminate\Support\Facades\Schedule;

// Hosted demo: wipe visitor changes and re-seed once a day (each run wakes
// the serverless database, so keep it rare).
// Set DEMO_RESET_SCHEDULE=false (default) anywhere you don't want this.
if (env('DEMO_RESET_SCHEDULE', false)) {
    Schedule::command('demo:reset')->daily()->withoutOverlapping();
}
