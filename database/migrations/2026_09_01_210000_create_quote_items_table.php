<?php

declare(strict_types=1);

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
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            // A snapshot: frozen at write time, never joined from a product
            // catalogue or anywhere else, so a historical quote's line items
            // can never change because something upstream did.
            $table->string('description');
            $table->integer('quantity');
            $table->bigInteger('unit_price_minor');
            $table->bigInteger('line_total_minor');

            $table->timestamps();

            $table->index(['quote_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_items');
    }
};
