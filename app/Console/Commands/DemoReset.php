<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Packstub\Tenancy\Models\Tenant;

/**
 * Wipes everything visitors did on the hosted demo and re-seeds it.
 * Scheduled hourly in routes/console.php; harmless to run locally.
 *
 * Tenants are deleted first: deleting a tenant row fires stancl's
 * TenantDeleted → DeleteDatabase pipeline, so their databases go away too
 * (migrate:fresh alone would only wipe the central database).
 */
class DemoReset extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Drop every tenant (and its database), wipe the central database, re-seed the demo.';

    public function handle(): int
    {
        config(['queue.default' => 'sync']);

        Tenant::query()->each(function (Tenant $tenant) {
            $this->line("Deleting tenant {$tenant->slug} and its database…");
            $tenant->delete();
        });

        $this->call('migrate:fresh', ['--force' => true, '--seed' => true]);

        $this->info('Demo reset.');

        return self::SUCCESS;
    }
}
