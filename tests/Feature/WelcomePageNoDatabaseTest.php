<?php

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\get;

/**
 * The hosted demo runs on Laravel Serverless Postgres, which bills for every
 * hour the database is awake. The public landing page and the health check
 * are what crawlers and uptime pings hit around the clock, so neither may
 * touch the database — that is what lets Postgres hibernate between real
 * visits. Sessions therefore live in the cookie and the cache on disk.
 */
beforeEach(function () {
    config()->set('session.driver', 'cookie');
    config()->set('cache.default', 'file');

    $this->queries = [];

    Event::listen(QueryExecuted::class, function (QueryExecuted $event): void {
        $this->queries[] = $event->sql;
    });
});

it('renders the welcome page without a single database query', function () {
    get('http://'.config('packstub-tenancy.central_domain').'/')
        ->assertOk()
        ->assertSee('Packstub Tenancy');

    expect($this->queries)->toBe([]);
});

it('answers the health check without a single database query', function () {
    get('http://'.config('packstub-tenancy.central_domain').'/up')->assertOk();

    expect($this->queries)->toBe([]);
});

it('would have hit the database with the old database session driver', function () {
    config()->set('session.driver', 'database');

    get('http://'.config('packstub-tenancy.central_domain').'/')->assertOk();

    expect($this->queries)->not->toBe([]);
});
