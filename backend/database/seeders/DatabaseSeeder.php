<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedUsers();
        $this->seedMenuAndOrders();
    }

    private function seedUsers(): void
    {
        $roles = ['admin', 'manager', 'kitchen', 'staff', 'desk', 'pos', 'inventory'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $users = [
            ['name' => 'Admin', 'email' => 'admin@afc.com.ng', 'password' => 'Password123@', 'role' => 'admin'],
            ['name' => 'Kitchen', 'email' => 'kitchen@example.com', 'password' => 'password', 'role' => 'kitchen'],
            ['name' => 'Staff', 'email' => 'staff@example.com', 'password' => 'password', 'role' => 'staff'],
            ['name' => 'Desk', 'email' => 'desk@example.com', 'password' => 'password', 'role' => 'desk'],
            ['name' => 'POS', 'email' => 'pos@example.com', 'password' => 'password', 'role' => 'pos'],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => bcrypt($data['password']),
                    'is_active' => true,
                    'role' => $data['role'],
                    'approved_by' => 1,
                ]
            );
            if ($data['email'] === 'admin@afc.com.ng') {
                $user->update(['password' => bcrypt($data['password']), 'is_active' => true]);
            }
            $user->syncRoles($data['role']);
        }
    }

    private function seedMenuAndOrders(): void
    {
        if (Category::count() > 0 || MenuItem::count() > 0) {
            return;
        }

        $categories = [
            ['name' => 'Mains', 'description' => 'Hero dishes', 'sort_order' => 1],
            ['name' => 'Sides', 'description' => 'Perfect add-ons', 'sort_order' => 2],
            ['name' => 'Drinks', 'description' => 'Refreshing sips', 'sort_order' => 3],
        ];

        $categories = collect($categories)->map(fn ($cat) => Category::create($cat));

        $menuItems = [
            [
                'category' => 'Mains',
                'name' => 'Catfish Pepper Soup',
                'description' => 'Rich, spicy catfish pepper soup served with your choice of side.',
                'price' => 5000,
                'sides' => ['Rice', 'Yam', 'Plantain'],
                'image_url' => '/assets/meal-1.jpg',
                'sort_order' => 1,
            ],
            [
                'category' => 'Mains',
                'name' => 'Jollof Rice Special',
                'description' => 'Smoky peppers, butter-soft chicken, charred vegetables, plantain.',
                'price' => 3500,
                'image_url' => '/assets/meal-1.jpg',
                'sort_order' => 2,
            ],
            [
                'category' => 'Mains',
                'name' => 'Grilled Chicken Bowl',
                'description' => 'Herb basmati, roasted peppers, tamarind glaze.',
                'price' => 4200,
                'image_url' => '/assets/meal-2.jpg',
                'sort_order' => 3,
            ],
            [
                'category' => 'Sides',
                'name' => 'Plantain Slices',
                'description' => 'Caramelized edges, sea salt, suya spice.',
                'price' => 1500,
                'image_url' => '/assets/meal-3.jpg',
                'sort_order' => 4,
            ],
        ];

        $menuItems = collect($menuItems)->map(function ($item) use ($categories) {
            $category = $categories->firstWhere('name', $item['category']);

            return MenuItem::create([
                'category_id' => $category?->id,
                'name' => $item['name'],
                'slug' => Str::slug($item['name'] . '-' . Str::random(6)),
                'description' => $item['description'],
                'sides' => $item['sides'] ?? null,
                'price' => $item['price'],
                'image_url' => $item['image_url'],
                'sort_order' => $item['sort_order'],
            ]);
        });

        $order = Order::create([
            'code' => Str::upper(Str::random(8)),
            'status' => 'paid',
            'channel' => 'pos',
            'customer_name' => 'Walk-in Guest',
            'subtotal' => 3500,
            'tax' => 0,
            'discount' => 0,
            'total' => 3500,
            'paid_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItems[0]->id ?? null,
            'name' => 'Jollof Rice Special',
            'quantity' => 1,
            'unit_price' => 3500,
            'total' => 3500,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'amount' => 3500,
            'method' => 'cash',
            'paid_at' => now(),
        ]);
    }
}
