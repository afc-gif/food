<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Reset all menu items to available (not sold out) by default
        // Admin can then mark specific items as sold out if needed
        DB::table('menu_items')->update(['is_sold_out' => false]);
    }

    public function down(): void
    {
        // No rollback needed as this is a data correction
        // If you need to revert, manually update the database
    }
};
