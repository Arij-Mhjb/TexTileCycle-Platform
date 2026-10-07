<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user() || ! in_array($request->user()->role, $roles, true)) {
            abort(403, 'Vous ne disposez pas des autorisations nécessaires.');
        }

        if ($request->user()->isBrand() && $request->user()->brand?->onboarding_status !== 'approved') {
            return redirect()->route('brand.application-pending');
        }
        return $next($request);
    }
}
