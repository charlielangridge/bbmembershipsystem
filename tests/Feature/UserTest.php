<?php

use App\Models\User;

it('has a name', function () {
    // Arrange
    $user1 = User::factory()->create();
    $user2 = User::factory()->create([
        'given_name' => 'John',
        'family_name' => 'Doe',
    ]);

    // Act & Assert
    expect($user1->name)->not()->toBeNull()
        ->and($user2->name)->toBe('John Doe');
});

it('a payment day over 28 should return 1', function () {
    // Arrange
    $firstDay = User::factory()->create([
        'payment_day' => 1,
    ]);

    $randomCorrectDay = User::factory()->create([
        'payment_day' => fake()->numberBetween(1, 28),
    ]);

    $randomWrongDay = User::factory()->create([
        'payment_day' => fake()->numberBetween(29, 31),
    ]);


    // Act & Assert
    expect($firstDay->payment_day)->toBe(1)
        ->and($randomCorrectDay->payment_day)->toBeLessThanOrEqual(28)
        ->and($randomWrongDay->payment_day)->toBe(1);


});
