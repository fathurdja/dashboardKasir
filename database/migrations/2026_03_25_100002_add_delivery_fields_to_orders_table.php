<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('delivery_user_id')->nullable()->after('device_id')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('delivery_assignment_id')->nullable()->after('delivery_user_id')
                ->constrained('delivery_assignments')->nullOnDelete();
            $table->string('delivery_address')->nullable()->after('delivery_assignment_id');
            $table->enum('delivery_status', ['pending', 'on_the_way', 'delivered', 'returned'])
                ->nullable()->after('delivery_address');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['delivery_user_id']);
            $table->dropForeign(['delivery_assignment_id']);
            $table->dropColumn([
                'delivery_user_id',
                'delivery_assignment_id',
                'delivery_address',
                'delivery_status',
            ]);
        });
    }
};
