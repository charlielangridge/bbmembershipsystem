<?php

namespace App;

use App\Models\User;

enum MemberVisibility: string
{
    case PubliclyListed = 'public';
    case MembersOnly = 'members';

    /**
     * Determine whether this visibility permits the current audience.
     */
    public function permits(?User $viewer): bool
    {
        return $this === self::PubliclyListed || self::permitsMembersOnlyAccess($viewer);
    }

    /**
     * Determine whether the current audience qualifies as a verified member.
     */
    public static function permitsMembersOnlyAccess(?User $viewer): bool
    {
        return $viewer?->hasVerifiedEmail() === true;
    }
}
