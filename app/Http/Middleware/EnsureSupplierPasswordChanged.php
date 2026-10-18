<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Un fournisseur avec un mot de passe provisoire doit d'abord choisir le sien. */
class EnsureSupplierPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password) {
            return redirect()->route('supplier.password.edit');
        }

        return $next($request);
    }
}
