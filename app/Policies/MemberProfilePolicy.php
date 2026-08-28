<?php

namespace App\Policies;

use App\Models\MemberProfile;
use App\Models\User;

class MemberProfilePolicy
{
    /**
     * Determine whether the audience can browse the privacy-filtered directory.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the audience can view the member profile.
     */
    public function view(?User $user, MemberProfile $memberProfile): bool
    {
        return $memberProfile->directory_visibility->permits($user);
    }

    /**
     * Determine whether the audience can view the member's profile photo.
     */
    public function viewPhoto(?User $user, MemberProfile $memberProfile): bool
    {
        return $memberProfile->directory_visibility->permits($user)
            && $memberProfile->photo_visibility->permits($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MemberProfile $memberProfile): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MemberProfile $memberProfile): bool
    {
        return false;
    }
}
