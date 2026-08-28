<?php

namespace App\Http\Controllers;

use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    /**
     * Display profiles visible to the current audience.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', MemberProfile::class);

        $members = MemberProfile::query()
            ->visibleTo($request->user())
            ->with('user')
            ->latest('id')
            ->get()
            ->map(fn (MemberProfile $profile): array => $this->profileSummary($profile, $request->user()));

        return Inertia::render('members/Index', [
            'members' => $members,
        ]);
    }

    /**
     * Display a member profile when it is visible to the current audience.
     */
    public function show(Request $request, User $member): Response
    {
        $profile = $member->memberProfile()->firstOrFail();

        abort_unless(Gate::allows('view', $profile), 404);

        return Inertia::render('members/Show', [
            'member' => [
                ...$this->profileSummary($profile, $request->user()),
                'description' => $profile->description,
            ],
        ]);
    }

    /**
     * @return array{id: int, name: string, tagline: string|null, photo_url: string|null}
     */
    private function profileSummary(MemberProfile $profile, ?User $viewer): array
    {
        return [
            'id' => $profile->user->id,
            'name' => $profile->user->name,
            'tagline' => $profile->tagline,
            'photo_url' => $profile->profile_photo_path !== null && $profile->photo_visibility->permits($viewer)
                ? route('members.photo', $profile->user)
                : null,
        ];
    }
}
