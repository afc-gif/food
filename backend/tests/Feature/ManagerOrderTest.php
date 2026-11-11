<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\User;
use App\Services\BusinessHours;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ManagerOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_and_complete_a_walk_in_order(): void
    {
        $manager = $this->createUserWithRole('manager');
        $item = $this->createMenuItem(['price' => 2500, 'stock' => 5]);

        $response = $this->actingAs($manager)->postJson('/api/manager/orders', [
            'channel' => 'pos',
            'customer_name' => 'Walk-in Customer',
            'customer_phone' => '08012345678',
            'service' => 'Dine-in',
            'note' => 'Table 4',
            'send_to_kitchen' => true,
            'items' => [[
                'menu_item_id' => $item->id,
                'quantity' => 2,
                'side_choice' => 'Plantain',
            ]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('channel', 'pos')
            ->assertJsonPath('customer_name', 'Walk-in Customer')
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('kitchen_status', 'queued')
            ->assertJsonPath('items.0.side_choice', 'Plantain')
            ->assertJsonPath('total', '5000.00');

        $order = Order::findOrFail($response->json('id'));
        $this->assertSame($manager->id, $order->created_by);
        $this->assertSame('Service: Dine-in | Note: Table 4', $order->kitchen_note);
        $this->assertTrue($order->stock_deducted);
        $this->assertSame(3, $item->fresh()->stock);

        $this->actingAs($manager)->postJson("/api/orders/{$order->id}/status", [
            'status' => 'completed',
        ])->assertOk()->assertJsonPath('status', 'completed')
            ->assertJsonPath('kitchen_status', 'served');

        $this->assertSame(3, $item->fresh()->stock);
    }

    public function test_manager_can_create_paid_online_order_when_online_ordering_is_closed(): void
    {
        $businessHours = Mockery::mock(BusinessHours::class);
        $businessHours->shouldReceive('availability')
            ->once()
            ->andReturn(['is_open' => false, 'message' => 'Online ordering is closed.']);
        $this->app->instance(BusinessHours::class, $businessHours);

        $manager = $this->createUserWithRole('manager');
        $item = $this->createMenuItem(['price' => 1800, 'stock' => null]);

        $response = $this->actingAs($manager)->postJson('/api/manager/orders', [
            'channel' => 'web',
            'customer_name' => 'Online Customer',
            'customer_phone' => '08098765432',
            'service' => 'Delivery',
            'note' => '12 Market Road',
            'payment' => [
                'amount' => 1,
                'method' => 'transfer',
                'reference' => 'CUSTOMER-REF',
            ],
            'items' => [[
                'menu_item_id' => $item->id,
                'quantity' => 2,
            ]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('channel', 'web')
            ->assertJsonPath('status', 'paid')
            ->assertJsonPath('total', '3600.00')
            ->assertJsonPath('payments.0.amount', '3600.00')
            ->assertJsonPath('payments.0.method', 'transfer');

        $order = Order::findOrFail($response->json('id'));
        $this->assertSame($manager->id, $order->created_by);
        $this->assertNotNull($order->paid_at);
        $this->assertSame('Service: Delivery | Note: 12 Market Road', $order->kitchen_note);

        $this->postJson('/api/orders', [
            'channel' => 'web',
            'items' => [['menu_item_id' => $item->id, 'quantity' => 1]],
        ])->assertStatus(409)->assertJsonPath('message', 'Online ordering is closed.');

        $this->assertSame(1, Order::count());
    }

    public function test_manager_order_endpoint_is_not_available_to_guests_or_staff(): void
    {
        $item = $this->createMenuItem();
        $payload = ['items' => [['menu_item_id' => $item->id, 'quantity' => 1]]];

        $this->postJson('/api/manager/orders', $payload)->assertUnauthorized();

        $staff = $this->createUserWithRole('staff');
        $this->actingAs($staff)->postJson('/api/manager/orders', $payload)->assertForbidden();

        $this->assertSame(0, Order::count());
    }

    private function createUserWithRole(string $roleName): User
    {
        Role::findOrCreate($roleName, 'web');
        $user = User::factory()->create([
            'role' => $roleName,
            'is_active' => true,
        ]);
        $user->assignRole($roleName);

        return $user;
    }

    private function createMenuItem(array $attributes = []): MenuItem
    {
        $category = Category::create([
            'name' => 'Test Menu',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return MenuItem::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Test Dish',
            'slug' => 'test-dish-'.uniqid(),
            'price' => 1000,
            'stock' => 10,
            'is_active' => true,
        ], $attributes));
    }
}
