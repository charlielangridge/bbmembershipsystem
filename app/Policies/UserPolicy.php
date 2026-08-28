<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can browse member accounts.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('members.manage');
    }

    /**
     * Determine whether the user can view the account.
     */
    public function view(User $user, User $account): bool
    {
        return $user->is($account) || $user->can('members.manage');
    }

    /**
     * Member creation is not available in the read-only administration resource.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Member updates are not available in the read-only administration resource.
     */
    public function update(User $user, User $account): bool
    {
        return false;
    }

    /**
     * Member deletion is not available in the read-only administration resource.
     */
    public function delete(User $user, User $account): bool
    {
        return false;
    }

    /**
     * Bulk member deletion is not available in the read-only administration resource.
     */
    public function deleteAny(User $user): bool
    {
        return false;
    }
}
