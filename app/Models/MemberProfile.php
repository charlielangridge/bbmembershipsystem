<?php

namespace App\Models;

use App\MemberVisibility;
use Database\Factories\MemberProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $tagline
 * @property string|null $description
 * @property MemberVisibility $directory_visibility
 * @property string|null $profile_photo_path
 * @property MemberVisibility $photo_visibility
 * @property-read User $user
 */
#[Fillable(['user_id', 'tagline', 'description', 'directory_visibility', 'profile_photo_path', 'photo_visibility'])]
class MemberProfile extends Model
{
    /** @use HasFactory<MemberProfileFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'directory_visibility' => 'members',
        'photo_visibility' => 'members',
    ];

    /**
     * Get the member that owns this profile.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Limit profiles to those visible to the current audience.
     *
     * @param  Builder<MemberProfile>  $query
     * @return Builder<MemberProfile>
     */
    #[Scope]
    protected function visibleTo(Builder $query, ?User $viewer): Builder
    {
        if (MemberVisibility::permitsMembersOnlyAccess($viewer)) {
            return $query;
        }

        return $query->where('directory_visibility', MemberVisibility::PubliclyListed);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'directory_visibility' => MemberVisibility::class,
            'photo_visibility' => MemberVisibility::class,
        ];
    }
}
