<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate;

class AuthenticateFilament extends Authenticate
{
    /**
     * Redirect unauthenticated administrators through Fortify.
     */
    protected function redirectTo(mixed $request): string
    {
        return route('login');
    }
}
