<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $categories = [
        'Breakfast',
        'Lunch & Brunch',
        'Rice & Beans Dishes',
        'Soups & Swallows',
        'Wraps & Burgers',
        'Sides & Extras',
        'Drinks & Teas',
        'Breads & Snacks',
    ];

    private array $imageSources = [
        'Breakfast' => 'english breakfast',
        'Lunch & Brunch' => 'signature dishes',
        'Rice & Beans Dishes' => 'signature dishes',
        'Soups & Swallows' => 'soups & swallow',
        'Wraps & Burgers' => 'signature dishes',
        'Sides & Extras' => 'sides',
        'Drinks & Teas' => 'drinks',
        'Breads & Snacks' => 'banana bread',
    ];

    public function up(): void
    {
        DB::transaction(function () {
            $existingCategories = DB::table('categories')->get(['id', 'name', 'image_url']);
            $oldNamesById = $existingCategories->mapWithKeys(
                fn ($category) => [$category->id => mb_strtolower(trim($category->name))]
            );
            $existingCategoriesByName = $existingCategories->keyBy(
                fn ($category) => mb_strtolower(trim($category->name))
            );
            $categoryIds = [];

            foreach ($this->categories as $index => $name) {
                $category = DB::table('categories')
                    ->where('name', $name)
                    ->orderBy('id')
                    ->first();

                if (! $category) {
                    $id = DB::table('categories')->insertGetId([
                        'name' => $name,
                        'is_active' => true,
                        'sort_order' => $index + 1,
                        'image_url' => $existingCategoriesByName
                            ->get($this->imageSources[$name])?->image_url,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $id = $category->id;
                    DB::table('categories')->where('id', $id)->update([
                        'is_active' => true,
                        'sort_order' => $index + 1,
                        'image_url' => $category->image_url ?: $existingCategoriesByName
                            ->get($this->imageSources[$name])?->image_url,
                        'updated_at' => now(),
                    ]);
                }

                $categoryIds[$name] = $id;
            }

            foreach (DB::table('menu_items')->get(['id', 'category_id', 'name']) as $item) {
                $categoryName = $this->categoryForItem(
                    mb_strtolower(trim($item->name)),
                    $oldNamesById->get($item->category_id)
                );

                DB::table('menu_items')->where('id', $item->id)->update([
                    'category_id' => $categoryIds[$categoryName],
                ]);
            }

            DB::table('categories')
                ->whereNotIn('id', array_values($categoryIds))
                ->update(['is_active' => false, 'updated_at' => now()]);
        });
    }

    public function down(): void
    {
        throw new RuntimeException(
            'The menu category reassignment cannot be rolled back automatically without restoring the previous item-to-category mapping.'
        );
    }

    private function categoryForItem(string $itemName, ?string $oldCategory): string
    {
        if (
            str_contains($itemName, 'soup')
            || str_contains($itemName, 'seafood okra')
            || $itemName === 'semovita'
        ) {
            return 'Soups & Swallows';
        }

        if (
            str_contains($itemName, 'burger')
            || str_contains($itemName, 'sandwich')
            || str_contains($itemName, 'wrap')
        ) {
            return 'Wraps & Burgers';
        }

        if (in_array($oldCategory, ['banana bread', 'freshly baked snacks', 'breads & snacks'], true)) {
            return 'Breads & Snacks';
        }

        if (in_array($oldCategory, ['english breakfast', 'nigerian breakfast', 'breakfast'], true)) {
            return 'Breakfast';
        }

        if (in_array($oldCategory, ['drinks', 'drinks & teas'], true)) {
            return 'Drinks & Teas';
        }

        if (in_array($oldCategory, ['soups & swallow', 'soups & swallows'], true)) {
            return 'Soups & Swallows';
        }

        if (in_array($oldCategory, ['proteins', 'sides', 'sides & extras'], true)) {
            return 'Sides & Extras';
        }

        if ($oldCategory === 'wraps & burgers') {
            return 'Wraps & Burgers';
        }

        if (in_array($oldCategory, ['rice & beans dishes'], true)) {
            return 'Rice & Beans Dishes';
        }

        if (in_array($oldCategory, ['lunch & brunch'], true)) {
            return 'Lunch & Brunch';
        }

        if ($oldCategory === 'signature dishes') {
            return str_contains($itemName, 'rice') || str_contains($itemName, 'beans')
                ? 'Rice & Beans Dishes'
                : 'Lunch & Brunch';
        }

        if (str_contains($itemName, 'rice') || str_contains($itemName, 'beans')) {
            return 'Rice & Beans Dishes';
        }

        return 'Sides & Extras';
    }
};
