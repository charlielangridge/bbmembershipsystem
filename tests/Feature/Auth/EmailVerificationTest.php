<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::emailVerification());
});

function verificationUrlFor(User $user, ?string $hash = null, ?int $id = null): string
{
    return URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $id ?? $user->id, 'hash' => $hash ?? sha1($user->email)],
    );
}

it('renders the email verification screen', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('verification.notice'))->assertOk();
});

it('verifies an email', function () {
    $user = User::factory()->unverified()->create();
    Event::fake();

    $response = $this->actingAs($user)->get(verificationUrlFor($user));

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
});

it('does not verify an email with an invalid hash', function () {
    $user = User::factory()->unverified()->create();
    Event::fake();

    $this->actingAs($user)->get(verificationUrlFor($user, sha1('wrong-email')));

    Event::assertNotDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('does not verify an email with an invalid user id', function () {
    $user = User::factory()->unverified()->create();
    Event::fake();

    $this->actingAs($user)->get(verificationUrlFor($user, id: 123));

    Event::assertNotDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('redirects a verified user away from the verification prompt', function () {
    $user = User::factory()->create();
    Event::fake();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertRedirect(route('dashboard', absolute: false));

    Event::assertNotDispatched(Verified::class);
});

it('redirects an already verified user without dispatching another event', function () {
    $user = User::factory()->create();
    Event::fake();

    $this->actingAs($user)
        ->get(verificationUrlFor($user))
        ->assertRedirect(route('dashboard', absolute: false).'?verified=1');

    Event::assertNotDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});
