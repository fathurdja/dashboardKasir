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
        Schema::create('stock_monitorings', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->index();
            $table->string('kdklp', 50)->index();
            $table->integer('stock_masuk')->default(0);
            $table->integer('stock_terjual')->default(0);
            $table->integer('sisa_stock')->default(0);
            $table->integer('total_transaksi')->default(0);
            $table->decimal('total_nominal', 15, 2)->default(0.00);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['tanggal', 'kdklp']);
        });

        Schema::create('stock_entries', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->index();
            $table->string('kdklp', 50)->index();
            $table->string('tyunit', 20)->nullable()->index();
            $table->string('tipe', 50)->default('MASUK');
            $table->integer('jumlah')->default(0);
            $table->integer('stok_sebelum')->default(0);
            $table->integer('stok_sesudah')->default(0);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_entries');
        Schema::dropIfExists('stock_monitorings');
    }
};
