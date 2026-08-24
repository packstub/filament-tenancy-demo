<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Stancl\Tenancy\Database\TenantDatabaseManagers\SQLiteDatabaseManager;

/**
 * Base class for tenant-aware tests — the pattern from the plugin's testing
 * docs: sync queue (provisioning finishes inline), central DB in memory, and
 * tenant SQLite files in a per-process temp dir that is wiped on teardown.
 */
abstract class TenantTestCase extends TestCase
{
    use RefreshDatabase;

    protected string $tenantDbPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantDbPath = sys_get_temp_dir().'/tenancy-demo-tests-'.getmypid();

        if (! is_dir($this->tenantDbPath)) {
            mkdir($this->tenantDbPath, 0777, true);
        }

        SQLiteDatabaseManager::$path = $this->tenantDbPath;
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        foreach (glob($this->tenantDbPath.'/*') ?: [] as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }
}
