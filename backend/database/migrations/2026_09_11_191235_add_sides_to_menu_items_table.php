<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('menu_items') && ! Schema::hasColumn('menu_items', 'sides')) {
            Schema::table('menu_items', function (Blueprint $table) {
                $table->json('sides')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('menu_items') && Schema::hasColumn('menu_items', 'sides')) {
            Schema::table('menu_items', function (Blueprint $table) {
                $table->dropColumn('sides');
            });
        }
    }
};
