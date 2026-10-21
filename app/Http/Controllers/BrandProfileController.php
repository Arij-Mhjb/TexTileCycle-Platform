<?php

namespace App\Http\Controllers;

use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BrandProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $brand = $request->user()->brand;
        $fields = ['name', 'legal_name', 'description', 'sector', 'founded_year', 'employee_range', 'website',
            'linkedin_url', 'country', 'city', 'address', 'contact_email', 'phone', 'commitments', 'target_markets', 'logo_path'];
        $completed = collect($fields)->filter(fn ($field) => filled($brand->{$field}))->count();

        return view('brand.profile', ['brand' => $brand, 'profileCompletion' => (int) round(100 * $completed / count($fields))]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255', 'legal_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000', 'sector' => 'nullable|string|max:150',
            'founded_year' => ['nullable', 'integer', 'min:1800', 'max:'.now()->year],
            'employee_range' => ['nullable', Rule::in(['1-9', '10-49', '50-249', '250+'])],
            'website' => 'nullable|url:http,https|max:255', 'linkedin_url' => 'nullable|url:http,https|max:255',
            'country' => 'nullable|string|max:100', 'city' => 'nullable|string|max:120', 'address' => 'nullable|string|max:255',
            'commitments' => 'nullable|string|max:5000', 'target_markets' => 'nullable|string|max:2000',
            'contact_email' => 'nullable|email:rfc|max:255',
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+() .-]+$/'],
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072', 'remove_logo' => 'nullable|boolean',
        ], ['phone.regex' => 'Le numéro de téléphone contient des caractères non autorisés.']);

        $brand = $request->user()->brand;
        $oldLogo = $brand->logo_path;
        if ($request->boolean('remove_logo')) {
            $data['logo_path'] = null;
        }
        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('brands', 'public');
        }
        unset($data['logo'], $data['remove_logo']);
        $brand->update($data);
        if ($oldLogo && $oldLogo !== $brand->logo_path) {
            Storage::disk('public')->delete($oldLogo);
        }
        app(ActivityService::class)->log($request->user()->id, $brand->id, 'brand.profile_updated', $brand);

        return back()->with('success', 'Profil de marque mis à jour.');
    }
}
