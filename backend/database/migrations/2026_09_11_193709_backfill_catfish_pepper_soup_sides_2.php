<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill sides for any item that has 'catfish' OR 'pepper soup' in the name
        // and whose sides column is null or an empty JSON value.
        DB::table('menu_items')
            ->where(function ($q) {
                $q->whereRaw("LOWER(name) LIKE '%catfish%'")
                  ->orWhereRaw("(LOWER(name) LIKE '%pepper%' AND LOWER(name) LIKE '%soup%')");
            })
            ->where(function ($q) {
                $q->whereNull('sides')
                  ->orWhereRaw("CAST(sides AS TEXT) IN (?, ?, ?)", ['[]', '""', 'null']);
            })
            ->update(['sides' => json_encode(['Rice', 'Yam', 'Plantain'])]);
    }

    public function down(): void
    {
        // no-op
    }
};
