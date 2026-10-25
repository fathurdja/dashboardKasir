<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('munit')) {
            Schema::create('munit', function (Blueprint $table) {
                $table->string('TYUNIT', 20)->primary();
                $table->string('NTYUNIT', 100);
                $table->string('kdklp', 50)->nullable();
                $table->decimal('hjual', 15, 2)->default(0);
                $table->integer('stock')->default(0);
                $table->string('cmodule', 50)->nullable();
                $table->string('userup', 60)->nullable();
                $table->timestamp('tglup')->nullable();
            });
        }

        if (!Schema::hasTable('transaksi')) {
            Schema::create('transaksi', function (Blueprint $table) {
                $table->string('id_transaksi', 50)->primary();
                $table->string('customer_name');
                $table->text('alamat')->nullable();
                $table->dateTime('tanggal');
                $table->decimal('total', 15, 2)->default(0);
                $table->string('status', 20)->default('BON');
            });
        }

        if (!Schema::hasTable('transaksi_items')) {
            Schema::create('transaksi_items', function (Blueprint $table) {
                $table->id();
                $table->string('id_transaksi', 50);
                $table->string('tyunit', 20);
                $table->string('nama_barang', 100);
                $table->decimal('harga', 15, 2)->default(0);
                $table->integer('quantity')->default(1);
                $table->integer('bonus')->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);

                $table->foreign('id_transaksi')->references('id_transaksi')->on('transaksi')->onDelete('cascade');
                $table->foreign('tyunit')->references('TYUNIT')->on('munit')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_items');
        Schema::dropIfExists('transaksi');
        Schema::dropIfExists('munit');
    }
};
