<?php

use App\Models\User;

it('authorizes users through permissions inherited from roles', function () {
    $administrator = assignRoleWithPermissions(
        User::factory()->create(),
        'admin',
        ['admin-panel.access'],
    );
    $member = User::factory()->create();

    expect($administrator->can('admin-panel.access'))->toBeTrue()
        ->and($member->can('admin-panel.access'))->toBeFalse();
});
