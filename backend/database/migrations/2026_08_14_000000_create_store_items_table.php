<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('store_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();      // free-text: "Dry Goods", "Cleaning", etc.
            $table->decimal('quantity', 10, 2)->default(0);
            $table->string('unit')->default('pcs');      // kg, L, pcs, bags, etc.
            $table->decimal('low_stock_threshold', 10, 2)->default(5);
            $table->text('supplier_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_items');
    }
};
