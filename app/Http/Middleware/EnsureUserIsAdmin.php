<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Block non-admin users from accessing the Filament panel.
     * Packers and unauthenticated users are redirected to the login page.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Access restricted to administrators only.');
        }

        return $next($request);
    }
}
