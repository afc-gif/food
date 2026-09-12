<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('expenses') && ! Schema::hasColumn('expenses', 'buyer_name')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->string('buyer_name')->nullable()->after('category');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('expenses') && Schema::hasColumn('expenses', 'buyer_name')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropColumn('buyer_name');
            });
        }
    }
};
