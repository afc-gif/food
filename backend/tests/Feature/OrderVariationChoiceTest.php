<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderVariationChoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_requires_a_configured_variation_choice(): void
    {
        $menuItem = $this->makeMenuItem();

        foreach ([null, 'French toast'] as $choice) {
            $item = [
                'menu_item_id' => $menuItem->id,
                'quantity' => 1,
            ];
            if ($choice !== null) {
                $item['variation_choice'] = $choice;
            }

            $this->postJson('/api/orders', [
                'channel' => 'pos',
                'send_to_kitchen' => false,
                'items' => [$item],
            ])->assertUnprocessable();
        }

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_saves_the_canonical_configured_variation_choice(): void
    {
        $menuItem = $this->makeMenuItem();

        $response = $this->postJson('/api/orders', [
            'channel' => 'pos',
            'send_to_kitchen' => false,
            'items' => [[
                'menu_item_id' => $menuItem->id,
                'quantity' => 2,
                'variation_choice' => 'waFfles',
            ]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('items.0.variation_choice', 'Waffles')
            ->assertJsonPath('items.0.name', 'Breakfast Combo — Waffles');

        $this->assertDatabaseHas('order_items', [
            'menu_item_id' => $menuItem->id,
            'variation_choice' => 'Waffles',
            'quantity' => 2,
        ]);
    }

    public function test_admin_menu_item_saves_trimmed_variation_choices(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->postJson('/api/menu-items', [
            'name' => 'Breakfast Combo',
            'price' => 5000,
            'variation_options' => " Waffles \n Pancakes \n",
        ]);

        $response->assertCreated()
            ->assertJsonPath('variation_options', ['Waffles', 'Pancakes']);
    }

    private function makeMenuItem(): MenuItem
    {
        return MenuItem::create([
            'name' => 'Breakfast Combo',
            'slug' => 'breakfast-combo-test',
            'price' => 5000,
            'is_active' => true,
            'variation_options' => ['Waffles', 'Pancakes'],
        ]);
    }
}
