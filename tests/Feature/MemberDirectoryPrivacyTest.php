<?php

use App\MemberVisibility;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

it('shows guests only profiles explicitly listed publicly', function () {
    $publicMember = User::factory()->create([
        'name' => 'Public Member',
        'email' => 'public@example.com',
    ]);
    MemberProfile::factory()->publiclyListed()->for($publicMember)->create();

    $privateMember = User::factory()->create([
        'name' => 'Private Member',
        'email' => 'private@example.com',
    ]);
    MemberProfile::factory()->for($privateMember)->create();

    $this->get(route('members.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('members/Index')
            ->has('members', 1)
            ->where('members.0.id', $publicMember->id)
            ->where('members.0.name', 'Public Member')
            ->missing('members.0.email')
        );
});

it('shows verified members profiles restricted to the membership', function () {
    $viewer = User::factory()->create();
    $member = User::factory()->create([
        'name' => 'Members Only',
        'email' => 'members-only@example.com',
    ]);
    MemberProfile::factory()->for($member)->create();

    $this->actingAs($viewer)
        ->get(route('members.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('members/Index')
            ->has('members', 1)
            ->where('members.0.id', $member->id)
            ->where('members.0.name', 'Members Only')
            ->missing('members.0.email')
        );
});

it('conceals member-only profile details from guests', function () {
    $member = User::factory()->create();
    MemberProfile::factory()->for($member)->create();

    $this->get(route('members.show', $member))
        ->assertNotFound();
});

it('shows public profile details without exposing account data', function () {
    $member = User::factory()->create([
        'name' => 'Visible Member',
        'email' => 'private-account@example.com',
    ]);
    MemberProfile::factory()->publiclyListed()->for($member)->create([
        'tagline' => 'Makes useful things.',
        'description' => 'A deliberately public profile description.',
    ]);

    $this->get(route('members.show', $member))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('members/Show')
            ->where('member.id', $member->id)
            ->where('member.name', 'Visible Member')
            ->where('member.tagline', 'Makes useful things.')
            ->where('member.description', 'A deliberately public profile description.')
            ->missing('member.email')
        );
});

it('shows verified members member-only profile details', function () {
    $viewer = User::factory()->create();
    $member = User::factory()->create();
    MemberProfile::factory()->for($member)->create();

    $this->actingAs($viewer)
        ->get(route('members.show', $member))
        ->assertOk();
});

it('conceals member-only profile details from unverified users', function () {
    $viewer = User::factory()->unverified()->create();
    $member = User::factory()->create();
    MemberProfile::factory()->for($member)->create();

    $this->actingAs($viewer)
        ->get(route('members.show', $member))
        ->assertNotFound();
});

it('serves an explicitly public profile photo through its protected route', function () {
    Storage::fake('local');
    $image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
    Storage::disk('local')->put('member-photos/public.png', $image);

    $member = User::factory()->create();
    MemberProfile::factory()->publiclyListed()->for($member)->create([
        'profile_photo_path' => 'member-photos/public.png',
        'photo_visibility' => MemberVisibility::PubliclyListed,
    ]);

    $this->get(route('members.photo', $member))
        ->assertOk()
        ->assertHeader('content-type', 'image/png');
});

it('conceals member-only profile photos from guests', function () {
    Storage::fake('local');
    Storage::disk('local')->put('member-photos/private.png', 'private-photo');

    $member = User::factory()->create();
    MemberProfile::factory()->publiclyListed()->for($member)->create([
        'profile_photo_path' => 'member-photos/private.png',
    ]);

    $this->get(route('members.photo', $member))
        ->assertNotFound();
});

it('does not let public photo visibility bypass a member-only profile', function () {
    Storage::fake('local');
    Storage::disk('local')->put('member-photos/public.png', 'public-photo');

    $member = User::factory()->create();
    MemberProfile::factory()->for($member)->create([
        'profile_photo_path' => 'member-photos/public.png',
        'photo_visibility' => MemberVisibility::PubliclyListed,
    ]);

    $this->get(route('members.photo', $member))
        ->assertNotFound();
});

it('serves member-only profile photos to verified members', function () {
    Storage::fake('local');
    Storage::disk('local')->put('member-photos/private.png', 'private-photo');

    $viewer = User::factory()->create();
    $member = User::factory()->create();
    MemberProfile::factory()->for($member)->create([
        'profile_photo_path' => 'member-photos/private.png',
    ]);

    $this->actingAs($viewer)
        ->get(route('members.photo', $member))
        ->assertOk();
});

it('only advertises photo URLs visible to the current audience', function () {
    $protectedPhotoMember = User::factory()->create();
    MemberProfile::factory()->publiclyListed()->for($protectedPhotoMember)->create([
        'profile_photo_path' => 'member-photos/protected.png',
    ]);

    $publicPhotoMember = User::factory()->create();
    MemberProfile::factory()->publiclyListed()->for($publicPhotoMember)->create([
        'profile_photo_path' => 'member-photos/public.png',
        'photo_visibility' => MemberVisibility::PubliclyListed,
    ]);

    $this->get(route('members.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('members.0.id', $publicPhotoMember->id)
            ->where('members.0.photo_url', route('members.photo', $publicPhotoMember))
            ->where('members.1.id', $protectedPhotoMember->id)
            ->where('members.1.photo_url', null)
        );
});
