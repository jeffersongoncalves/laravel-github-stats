<?php

use Illuminate\Support\Facades\Cache;

it('returns health check response', function () {
    $response = $this->get('/api/health');

    $response->assertOk()
        ->assertJson([
            'status' => 'ok',
            'cache' => 'cold',
            'username' => 'testuser',
            'last_update' => 'never',
        ]);
});

it('reports a warm cache and last update once data is cached', function () {
    Cache::put('github:user_stats', ['name' => 'Test User'], 60);
    Cache::put('github:last_refresh', '2026-06-22T00:00:00+00:00', 60);

    $response = $this->get('/api/health');

    $response->assertOk()
        ->assertJson([
            'status' => 'ok',
            'cache' => 'warm',
            'username' => 'testuser',
            'last_update' => '2026-06-22T00:00:00+00:00',
        ]);
});
