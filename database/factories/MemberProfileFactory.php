<?php

namespace Database\Factories;

use App\MemberVisibility;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberProfile>
 */
class MemberProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tagline' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'directory_visibility' => MemberVisibility::MembersOnly,
            'profile_photo_path' => null,
            'photo_visibility' => MemberVisibility::MembersOnly,
        ];
    }

    /**
     * Make the profile visible outside the membership.
     */
    public function publiclyListed(): static
    {
        return $this->state(fn (): array => [
            'directory_visibility' => MemberVisibility::PubliclyListed,
        ]);
    }
}
