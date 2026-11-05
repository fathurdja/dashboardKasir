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
        if (Schema::hasTable('stock_monitorings')) {
            Schema::table('stock_monitorings', function (Blueprint $table) {
                if (!Schema::hasColumn('stock_monitorings', 'stok_awal')) {
                    $table->integer('stok_awal')->default(0)->after('kdklp');
                }
                if (!Schema::hasColumn('stock_monitorings', 'stok_tambahan')) {
                    $table->integer('stok_tambahan')->default(0)->after('stok_awal');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('stock_monitorings')) {
            Schema::table('stock_monitorings', function (Blueprint $table) {
                if (Schema::hasColumn('stock_monitorings', 'stok_tambahan')) {
                    $table->dropColumn('stok_tambahan');
                }
                if (Schema::hasColumn('stock_monitorings', 'stok_awal')) {
                    $table->dropColumn('stok_awal');
                }
            });
        }
    }
};
