<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminMenuPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_a_pdf_of_active_menu_items(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole('admin');

        $category = Category::create([
            'name' => 'Breakfast',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        MenuItem::create([
            'category_id' => $category->id,
            'name' => 'Pancakes',
            'slug' => 'pancakes-test',
            'price' => 7500,
            'is_active' => true,
        ]);
        MenuItem::create([
            'category_id' => $category->id,
            'name' => 'Archived Dish',
            'slug' => 'archived-dish-test',
            'price' => 5000,
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.menu.pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader(
            'content-disposition',
            'attachment; filename=acie-fraiche-menu-' . now()->format('Y-m-d') . '.pdf'
        );
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_menu_pdf_is_not_available_to_guests_or_non_admin_users(): void
    {
        $this->get(route('admin.menu.pdf'))->assertRedirect(route('login'));

        $staff = User::factory()->create(['role' => 'staff', 'is_active' => true]);

        $this->actingAs($staff)->get(route('admin.menu.pdf'))->assertForbidden();
    }
}
