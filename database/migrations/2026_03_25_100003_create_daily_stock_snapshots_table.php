<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_stock_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->date('date');
            $table->integer('opening_stock')->default(0);
            $table->integer('closing_stock')->default(0);
            $table->integer('sold_qty')->default(0);
            $table->integer('added_qty')->default(0)->comment('Stock masuk hari itu');
            $table->integer('bonus_qty')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'date'], 'unique_product_snapshot_per_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_stock_snapshots');
    }
};
