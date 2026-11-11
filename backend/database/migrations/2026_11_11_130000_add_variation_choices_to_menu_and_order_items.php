<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('menu_items') && ! Schema::hasColumn('menu_items', 'variation_options')) {
            Schema::table('menu_items', function (Blueprint $table) {
                $table->json('variation_options')->nullable();
            });
        }

        if (Schema::hasTable('order_items') && ! Schema::hasColumn('order_items', 'variation_choice')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->string('variation_choice', 100)->nullable()->after('side_choice');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'variation_choice')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('variation_choice');
            });
        }

        if (Schema::hasTable('menu_items') && Schema::hasColumn('menu_items', 'variation_options')) {
            Schema::table('menu_items', function (Blueprint $table) {
                $table->dropColumn('variation_options');
            });
        }
    }
};
