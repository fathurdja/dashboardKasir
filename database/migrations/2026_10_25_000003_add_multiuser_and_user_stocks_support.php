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
        // 1. Add role & username to users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role', 20)->default('user')->after('email');
            }
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username', 50)->nullable()->unique()->after('name');
            }
        });

        // 2. Add user_id & user_name to transaksi
        Schema::table('transaksi', function (Blueprint $table) {
            if (!Schema::hasColumn('transaksi', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id_transaksi')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('transaksi', 'user_name')) {
                $table->string('user_name', 100)->nullable()->after('user_id');
            }
        });

        // 3. Create user_stocks table
        if (!Schema::hasTable('user_stocks')) {
            Schema::create('user_stocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('tyunit', 20);
                $table->string('kdklp', 50)->nullable();
                $table->integer('kuota')->default(0);
                $table->integer('stock')->default(0);
                $table->timestamps();

                $table->unique(['user_id', 'tyunit']);
                $table->index(['user_id', 'kdklp']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_stocks');

        Schema::table('transaksi', function (Blueprint $table) {
            if (Schema::hasColumn('transaksi', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('transaksi', 'user_name')) {
                $table->dropColumn('user_name');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
            if (Schema::hasColumn('users', 'username')) {
                $table->dropColumn('username');
            }
        });
    }
};
