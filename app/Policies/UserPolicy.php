<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view the account.
     */
    public function view(User $user, User $account): bool
    {
        return $user->is($account) || $user->can('members.manage');
    }
}
