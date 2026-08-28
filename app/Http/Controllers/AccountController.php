<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * Display a member account.
     */
    public function show(User $account): Response
    {
        Gate::authorize('view', $account);

        return Inertia::render('account/Show', [
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'email' => $account->email,
                'email_verified_at' => $account->email_verified_at?->toISOString(),
                'created_at' => $account->created_at?->toISOString(),
            ],
        ]);
    }
}
