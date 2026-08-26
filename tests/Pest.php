<?php

use Tests\TenantTestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Every feature test is tenant-aware: sync queue, central DB in memory and
| tenant SQLite files in a per-process temp dir (see TenantTestCase).
|
*/

pest()->extend(TenantTestCase::class)->in('Feature');
