<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View { return view('auth.register'); }

    public function store(Request $request): RedirectResponse
    {
        $data=$request->validate([
            'name'=>['required','string','max:255'],
            'email'=>['required','string','lowercase','email','max:255','unique:'.User::class],
            'role'=>['required','in:consumer,brand'],
            'brand_name'=>['nullable','required_if:role,brand','string','max:255'],
            'password'=>['required','confirmed',Rules\Password::defaults()],
        ]);
        $user=User::create(['name'=>$data['name'],'email'=>$data['email'],'role'=>$data['role'],'password'=>Hash::make($data['password'])]);
        if($user->isBrand()) Brand::create(['user_id'=>$user->id,'name'=>$data['brand_name'],'slug'=>Str::slug($data['brand_name']).'-'.Str::lower(Str::random(5))]);
        event(new Registered($user)); Auth::login($user);
        return redirect()->route($user->isBrand() ? 'brand.dashboard' : 'consumer.home');
    }
}
