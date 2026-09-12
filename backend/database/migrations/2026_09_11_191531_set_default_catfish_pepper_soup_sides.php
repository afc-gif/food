<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('menu_items')
            ->where('name', 'LIKE', '%Catfish Pepper Soup%')
            ->update([
                'sides' => json_encode(['Rice', 'Yam', 'Plantain']),
            ]);
    }

    public function down(): void
    {
        // No-op
    }
};
