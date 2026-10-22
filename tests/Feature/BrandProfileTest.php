<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_can_complete_its_company_profile(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'brand']);
        $brand = Brand::create(['user_id' => $user->id, 'name' => 'Atelier', 'slug' => 'atelier', 'is_active' => true]);

        $this->actingAs($user)->patch(route('brand.profile.update'), [
            'name' => 'Atelier Vert', 'legal_name' => 'Atelier Vert SARL', 'sector' => 'Mode circulaire',
            'founded_year' => 2020, 'employee_range' => '10-49', 'country' => 'Tunisie', 'city' => 'Tunis',
            'contact_email' => 'contact@atelier.test', 'phone' => '+216 71 000 000',
            'website' => 'https://atelier.test', 'linkedin_url' => 'https://linkedin.com/company/atelier-vert',
            'description' => 'Marque textile responsable.', 'commitments' => 'Réduire les matières vierges.',
            'target_markets' => 'Tunisie et Europe', 'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertSessionHasNoErrors()->assertRedirect();

        $brand->refresh();
        $this->assertSame('Atelier Vert SARL', $brand->legal_name);
        $this->assertSame('Tunis', $brand->city);
        Storage::disk('public')->assertExists($brand->logo_path);
        $this->get(route('brand.profile.edit'))->assertOk()->assertSee('Complétion du profil');
        $this->get(route('brands.show', $brand))->assertOk()->assertSee('Mode circulaire')->assertSee('Tunis');
    }

    public function test_profile_rejects_invalid_business_information(): void
    {
        $user = User::factory()->create(['role' => 'brand']);
        Brand::create(['user_id' => $user->id, 'name' => 'Atelier', 'slug' => 'atelier', 'is_active' => true]);

        $this->actingAs($user)->patch(route('brand.profile.update'), [
            'name' => '', 'founded_year' => now()->year + 1, 'employee_range' => 'unknown',
            'website' => 'invalid', 'phone' => 'telephone?',
        ])->assertSessionHasErrors(['name', 'founded_year', 'employee_range', 'website', 'phone']);
    }
}
