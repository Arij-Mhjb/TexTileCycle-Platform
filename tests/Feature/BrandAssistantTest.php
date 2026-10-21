<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BrandAssistantTest extends TestCase
{
    use RefreshDatabase;

    private function brandUser(): User
    {
        $user = User::factory()->create(['role' => 'brand']);
        Brand::create(['user_id' => $user->id, 'name' => 'Atelier', 'slug' => 'atelier', 'is_active' => true]);

        return $user;
    }

    public function test_local_guide_helps_brand_without_ai_key(): void
    {
        config(['brand_assistant.key' => null]);
        $this->actingAs($this->brandUser())->postJson(route('brand.assistant.ask'), [
            'question' => 'Comment analyser les labels de mon produit ?', 'page' => 'brand/products',
        ])->assertOk()->assertJsonPath('mode', 'guide-local')
            ->assertJsonFragment(['label' => 'Analyse produit-label']);
    }

    public function test_configured_custom_model_answers_with_platform_context(): void
    {
        config(['brand_assistant.key' => 'test-key', 'brand_assistant.model' => 'ft:gpt-platform-test']);
        Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => 'Ouvrez le catalogue puis soumettez votre produit.']]]])]);

        $this->actingAs($this->brandUser())->postJson(route('brand.assistant.ask'), [
            'question' => 'Comment publier un produit ?', 'page' => 'brand/products',
        ])->assertOk()->assertJsonPath('mode', 'llm')->assertJsonPath('model', 'ft:gpt-platform-test');
        Http::assertSent(fn ($request) => $request['model'] === 'ft:gpt-platform-test'
            && str_contains($request['messages'][0]['content'], 'Parcours principal'));
    }

    public function test_assistant_is_reserved_for_brand_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->postJson('/brand/assistant', ['question' => 'Aide-moi'])->assertForbidden();
    }
}
