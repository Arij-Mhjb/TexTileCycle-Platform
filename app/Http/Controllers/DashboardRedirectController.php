<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $route=match($request->user()->role){'admin'=>'admin.dashboard','brand'=>'brand.dashboard',default=>'consumer.home'};
        return redirect()->route($route);
    }
}
