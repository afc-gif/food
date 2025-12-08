<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('kitchen_status')->default('pending')->after('status'); // pending, queued, prepping, ready, served
            $table->integer('kitchen_eta_minutes')->nullable()->after('kitchen_status');
            $table->timestamp('kitchen_eta_at')->nullable()->after('kitchen_eta_minutes');
            $table->timestamp('kitchen_sent_at')->nullable()->after('kitchen_eta_at');
            $table->text('kitchen_note')->nullable()->after('kitchen_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'kitchen_status',
                'kitchen_eta_minutes',
                'kitchen_eta_at',
                'kitchen_sent_at',
                'kitchen_note',
            ]);
        });
    }
};
