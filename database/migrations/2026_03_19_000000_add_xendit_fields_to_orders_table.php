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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('xendit_external_id')->nullable()->unique()->after('payment_method');
            $table->string('xendit_invoice_url')->nullable()->after('xendit_external_id');
            $table->string('xendit_status')->nullable()->after('xendit_invoice_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['xendit_external_id', 'xendit_invoice_url', 'xendit_status']);
        });
    }
};
