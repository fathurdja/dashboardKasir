<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('munit') && !Schema::hasColumn('munit', 'kuota')) {
            Schema::table('munit', function (Blueprint $table) {
                $table->integer('kuota')->default(0)->after('stock');
            });
        }

        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'kuota')) {
            Schema::table('products', function (Blueprint $table) {
                $table->integer('kuota')->default(0)->after('stock');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('munit') && Schema::hasColumn('munit', 'kuota')) {
            Schema::table('munit', function (Blueprint $table) {
                $table->dropColumn('kuota');
            });
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'kuota')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('kuota');
            });
        }
    }
};
