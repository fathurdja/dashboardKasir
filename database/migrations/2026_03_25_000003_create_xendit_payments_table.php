<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xendit_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('xendit_invoice_id')->unique();
            $table->string('xendit_invoice_url')->nullable();
            $table->string('qr_string')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('PENDING')->comment('PENDING, PAID, EXPIRED');
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->json('xendit_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xendit_payments');
    }
};
