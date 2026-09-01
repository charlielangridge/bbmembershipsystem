<?php

use Spatie\FlareClient\AttributesProviders\EmptyUserAttributesProvider;
use Spatie\LaravelFlare\AttributesProviders\LaravelUserAttributesProvider;

it('keeps Flare disabled until production credentials explicitly enable it', function () {
    expect(config('flare.key'))->toBeEmpty()
        ->and(config('flare.report'))->toBeFalse()
        ->and(config('flare.trace'))->toBeFalse()
        ->and(config('flare.log'))->toBeFalse()
        ->and(config('flare.enable_share_button'))->toBeFalse();
});

it('censors member request data and identity from Flare reports', function () {
    expect(config('flare.censor.client_ips'))->toBeTrue()
        ->and(config('flare.censor.cookies'))->toBeTrue()
        ->and(config('flare.censor.session'))->toBeTrue()
        ->and(config('flare.censor.body_fields'))->toContain(
            'current_password',
            'password',
            'password_confirmation',
            'recovery_code',
            'secret',
            'token',
        )
        ->and(app(LaravelUserAttributesProvider::class))
        ->toBeInstanceOf(EmptyUserAttributesProvider::class);
});
