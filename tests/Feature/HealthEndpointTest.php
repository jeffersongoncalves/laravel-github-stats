<?php

it('returns health check response', function () {
    $response = $this->get('/api/health');

    $response->assertOk()
        ->assertJson([
            'status' => 'ok',
            'cache' => 'cold',
            'username' => 'testuser',
        ]);
});
