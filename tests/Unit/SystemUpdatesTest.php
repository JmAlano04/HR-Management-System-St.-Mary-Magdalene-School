<?php

use App\Http\Controllers\SystemUpdatesController;
use App\Http\Middleware\TrackSystemDataUpdates;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class);

it('advances the shared version after a successful mutation', function (): void {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('where')
        ->with('id', 1)
        ->twice()
        ->andReturnSelf();
    $query->shouldReceive('value')
        ->with('version')
        ->once()
        ->andReturn(7);
    $query->shouldReceive('increment')
        ->with('version', 1, Mockery::on(fn (array $values): bool => isset($values['updated_at'])))
        ->once()
        ->andReturn(1);

    DB::shouldReceive('table')
        ->with('system_data_versions')
        ->twice()
        ->andReturn($query);

    $response = app(TrackSystemDataUpdates::class)->handle(
        Request::create('/leave-requests', 'POST'),
        fn (): Response => new Response('saved', 200),
    );

    expect($response->getStatusCode())->toBe(200);
});

it('returns the current version without exposing data and disables caching', function (): void {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('where')
        ->with('id', 1)
        ->once()
        ->andReturnSelf();
    $query->shouldReceive('value')
        ->with('version')
        ->once()
        ->andReturn('12');

    DB::shouldReceive('table')
        ->with('system_data_versions')
        ->once()
        ->andReturn($query);

    $response = app(SystemUpdatesController::class)->current();

    expect($response->getData(true))->toBe(['version' => 12])
        ->and($response->headers->get('Cache-Control'))->toBe('no-store, private');
});
