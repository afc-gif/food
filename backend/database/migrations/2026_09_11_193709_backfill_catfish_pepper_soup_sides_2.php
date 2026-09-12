<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill sides for any item that has 'catfish' OR 'pepper soup' in the name
        // and whose sides column is still null/empty
        DB::table('menu_items')
            ->where(function ($q) {
                $q->whereRaw("LOWER(name) LIKE '%catfish%'")
                  ->orWhereRaw("(LOWER(name) LIKE '%pepper%' AND LOWER(name) LIKE '%soup%')");
            })
            ->whereNull('sides')
            ->update(['sides' => json_encode(['Rice', 'Yam', 'Plantain'])]);

        // Also force-update even if sides column was set to [] or empty string
        DB::table('menu_items')
            ->where(function ($q) {
                $q->whereRaw("LOWER(name) LIKE '%catfish%'")
                  ->orWhereRaw("(LOWER(name) LIKE '%pepper%' AND LOWER(name) LIKE '%soup%')");
            })
            ->where(function ($q) {
                $q->where('sides', '[]')
                  ->orWhere('sides', '')
                  ->orWhere('sides', 'null');
            })
            ->update(['sides' => json_encode(['Rice', 'Yam', 'Plantain'])]);
    }

    public function down(): void
    {
        // no-op
    }
};
