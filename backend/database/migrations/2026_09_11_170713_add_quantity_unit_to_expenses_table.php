<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                if (! Schema::hasColumn('expenses', 'quantity')) {
                    $table->decimal('quantity', 10, 3)->nullable()->after('amount');
                }
                if (! Schema::hasColumn('expenses', 'unit')) {
                    $table->string('unit', 50)->nullable()->after('quantity');
                }
                if (! Schema::hasColumn('expenses', 'price_per_unit')) {
                    $table->decimal('price_per_unit', 12, 2)->nullable()->after('unit');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropColumn(array_filter(['quantity', 'unit', 'price_per_unit'], fn($col) => Schema::hasColumn('expenses', $col)));
            });
        }
    }
};
