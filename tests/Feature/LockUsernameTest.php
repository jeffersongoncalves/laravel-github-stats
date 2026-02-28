<?php

it('allows requests without username parameter', function () {
    $response = $this->get('/api/health');

    $response->assertOk();
});

it('allows requests with matching username', function () {
    $response = $this->get('/api/health?username=testuser');

    $response->assertOk();
});

it('blocks requests with wrong username', function () {
    $response = $this->get('/api/health?username=hacker');

    $response->assertForbidden();
});
