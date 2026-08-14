<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('store_items', function (Blueprint $table) {
            // Drop the old free-text category column
            $table->dropColumn('category');

            // Add a proper FK to store_categories
            $table->foreignId('store_category_id')
                ->nullable()
                ->after('name')
                ->constrained('store_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('store_items', function (Blueprint $table) {
            $table->dropForeign(['store_category_id']);
            $table->dropColumn('store_category_id');
            $table->string('category')->nullable()->after('name');
        });
    }
};
