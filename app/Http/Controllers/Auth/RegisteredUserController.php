<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyseBrandApplication;
use App\Models\Brand;
use App\Models\BrandApplication;
use App\Models\User;
use App\Notifications\BrandApplicationMail;
use App\Services\BrandQuestionnaire;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(BrandQuestionnaire $questionnaire): View
    {
        return view('auth.register', ['questions' => $questionnaire->questions()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'role' => ['required', 'in:consumer,brand'],
            'brand_name' => ['nullable', 'required_if:role,brand', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'answers' => ['required_if:role,brand', 'array'],
            'analysis_consent' => ['exclude_unless:role,brand', 'required', 'accepted'],
        ]);
        $answers = $data['role'] === 'brand' ? app(BrandQuestionnaire::class)->validate($data['answers']) : [];
        $user = DB::transaction(function () use ($data, $answers) {
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'role' => $data['role'], 'password' => Hash::make($data['password'])]);
            if ($user->isBrand()) {
                $brand = Brand::create(['user_id' => $user->id, 'name' => $data['brand_name'],
                    'slug' => Str::slug($data['brand_name']).'-'.Str::lower(Str::random(5)),
                    'country' => $answers['country'], 'contact_email' => $user->email,
                    'onboarding_status' => 'pending', 'is_active' => false]);
                $application = BrandApplication::create(['brand_id' => $brand->id, 'answers' => $answers,
                    'analysis_status' => config('brand_onboarding.ai_key') ? 'pending' : 'unavailable',
                    'analysis_summary' => config('brand_onboarding.ai_key') ? null : 'IA non configurée. Revue manuelle nécessaire.']);
                $user->notify(new BrandApplicationMail('Votre demande marque est prise en charge', [
                    'Votre inscription et le questionnaire de '.$brand->name.' ont bien été reçus.',
                    'Votre dossier est en cours de traitement. Une analyse d’orientation et une revue administrateur précèdent l’activation du compte.',
                    'Vous recevrez un email dès que votre compte sera prêt. La connexion à l’espace marque sera alors disponible.',
                ], route('login'), 'Page de connexion'));
                if (config('brand_onboarding.ai_key')) {
                    AnalyseBrandApplication::dispatch($application->id)->afterCommit();
                }
            }

            return $user;
        });
        if ($user->isBrand()) {
            return redirect()->route('brand.application-pending')->with('application_received', true);
        }
        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('consumer.home');
    }
}
