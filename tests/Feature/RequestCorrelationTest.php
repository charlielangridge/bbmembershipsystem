<?php

use Illuminate\Support\Str;

it('adds a generated request ID to every response', function () {
    $requestId = $this->get('/up')
        ->assertOk()
        ->headers->get('X-Request-ID');

    expect($requestId)
        ->toBeString()
        ->and(Str::isUuid($requestId))->toBeTrue();
});

it('preserves a valid upstream request ID', function () {
    $requestId = Str::uuid()->toString();

    $this->withHeader('X-Request-ID', $requestId)
        ->get('/up')
        ->assertOk()
        ->assertHeader('X-Request-ID', $requestId);
});

it('replaces an invalid upstream request ID', function () {
    $requestId = $this->withHeader('X-Request-ID', 'not-a-valid-request-id')
        ->get('/up')
        ->assertOk()
        ->headers->get('X-Request-ID');

    expect($requestId)
        ->toBeString()
        ->not->toBe('not-a-valid-request-id')
        ->and(Str::isUuid($requestId))->toBeTrue();
});
