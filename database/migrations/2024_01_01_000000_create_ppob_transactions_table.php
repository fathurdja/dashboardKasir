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
        Schema::create('ppob_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ref_id')->unique(); // Order ID internal
            $table->string('customer_number'); // No HP / ID Pelanggan
            $table->string('product_code'); // Kode produk IAK
            $table->decimal('price', 15, 2); // Harga jual
            $table->decimal('iak_price', 15, 2)->nullable(); // Harga modal dari IAK
            $table->string('status')->default('PENDING'); // PENDING, SUCCESS, FAILED
            $table->string('sn')->nullable(); // Serial Number (jika sukses)
            $table->text('iak_response')->nullable(); // Log response IAK
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ppob_transactions');
    }
};
