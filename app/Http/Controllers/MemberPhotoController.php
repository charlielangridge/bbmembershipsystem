<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberPhotoController extends Controller
{
    public function __invoke(User $member): StreamedResponse
    {
        $profile = $member->memberProfile()->firstOrFail();

        abort_unless(Gate::allows('viewPhoto', $profile), 404);

        $photoPath = $profile->profile_photo_path;
        $disk = Storage::disk('local');

        abort_unless($photoPath !== null && $disk->exists($photoPath), 404);

        return $disk->response($photoPath);
    }
}
