<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('device_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->decimal('received_amount', 15, 2)->nullable()->after('payment_method');
            $table->decimal('change_amount', 15, 2)->nullable()->after('received_amount');
            $table->string('customer_name')->nullable()->after('change_amount');
            $table->string('customer_phone')->nullable()->after('customer_name');
            $table->date('due_date')->nullable()->after('customer_phone');
            $table->text('notes')->nullable()->after('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
            $table->dropColumn([
                'device_id',
                'received_amount',
                'change_amount',
                'customer_name',
                'customer_phone',
                'due_date',
                'notes',
            ]);
        });
    }
};
