<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('store_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_item_id')->constrained('store_items')->cascadeOnDelete();
            $table->decimal('quantity_change', 10, 2);   // positive = restock, negative = usage/wastage
            $table->string('reason')->nullable();         // "Weekly restock", "Spillage", etc.
            $table->string('adjusted_by')->nullable();    // email of the user who made the change
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_adjustments');
    }
};
