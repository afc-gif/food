<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReplaceMenuCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_categories_are_replaced_and_menu_items_are_reassigned(): void
    {
        $categoryIds = [];
        foreach ([
            'English Breakfast',
            'Nigerian Breakfast',
            'Signature Dishes',
            'Soups & Swallow',
            'Sides',
            'Banana Bread',
            'Freshly Baked Snacks',
            'Drinks',
        ] as $name) {
            $categoryIds[$name] = Category::create([
                'name' => $name,
                'image_url' => $name === 'English Breakfast' ? '/images/breakfast.jpg' : null,
                'is_active' => true,
                'sort_order' => 1,
            ])->id;
        }

        $items = [
            ['name' => 'Akara & Pap', 'category' => 'Nigerian Breakfast', 'expected' => 'Breakfast'],
            ['name' => 'White Rice & Beans', 'category' => 'Signature Dishes', 'expected' => 'Rice & Beans Dishes'],
            ['name' => 'Chicken and chips', 'category' => 'Signature Dishes', 'expected' => 'Lunch & Brunch'],
            ['name' => 'Burger', 'category' => 'Signature Dishes', 'expected' => 'Wraps & Burgers'],
            ['name' => 'Goat Meat Pepper Soup Mini', 'category' => 'Sides', 'expected' => 'Soups & Swallows'],
            ['name' => 'Maxi Loafs', 'category' => 'Freshly Baked Snacks', 'expected' => 'Breads & Snacks'],
            ['name' => 'Tea', 'category' => 'Drinks', 'expected' => 'Drinks & Teas'],
            ['name' => 'Peppered Gizzard', 'category' => 'Sides', 'expected' => 'Sides & Extras'],
            ['name' => 'Coconut Rice', 'category' => null, 'expected' => 'Rice & Beans Dishes'],
            ['name' => 'Semovita', 'category' => null, 'expected' => 'Soups & Swallows'],
        ];

        $menuItems = [];
        foreach ($items as $index => $item) {
            $menuItems[$index] = MenuItem::create([
                'category_id' => $item['category'] ? $categoryIds[$item['category']] : null,
                'name' => $item['name'],
                'slug' => 'category-test-'.$index,
                'price' => 1000,
                'is_active' => true,
            ]);
        }

        $migration = require database_path('migrations/2026_11_11_140000_replace_menu_categories.php');
        $migration->up();

        $this->assertSame([
            'Breakfast',
            'Lunch & Brunch',
            'Rice & Beans Dishes',
            'Soups & Swallows',
            'Wraps & Burgers',
            'Sides & Extras',
            'Drinks & Teas',
            'Breads & Snacks',
        ], Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name')
            ->all());
        $this->assertSame(
            '/images/breakfast.jpg',
            Category::query()->where('name', 'Breakfast')->value('image_url')
        );

        foreach ($items as $index => $item) {
            $this->assertSame(
                $item['expected'],
                $menuItems[$index]->fresh()->category->name,
                $item['name']
            );
        }

        $this->assertSame(
            count($items),
            MenuItem::query()->whereNotNull('category_id')->count()
        );
    }
}
