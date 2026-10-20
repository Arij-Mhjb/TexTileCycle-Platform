<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepairPartnerWorkshopTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_partners_page_loads(): void
    {
        $response = $this->get(route('partners.index'));

        $response->assertOk();
    }

    public function test_admin_can_create_a_repair_partner(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin-repair@test.com',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.repair-partners.store'), [
            'name' => 'Atelier Vert',
            'type' => 'atelier',
            'city' => 'Tunis',
            'address' => 'Rue de l\'Innovation, 15',
            'description' => 'Atelier de réparation textile local.',
        ]);

        $response->assertRedirect(route('admin.repair-partners.index'));
        $this->assertDatabaseHas('repair_partners', [
            'name' => 'Atelier Vert',
            'slug' => 'atelier-vert',
        ]);
    }
}
