<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('tax_id')->nullable();
            $table->decimal('tax_rate', 5, 2)->default(11.00);
            $table->string('currency', 3)->default('IDR');
            $table->string('xendit_public_key')->nullable();
            $table->string('xendit_secret_key')->nullable();
            $table->string('xendit_webhook_token')->nullable();
            $table->string('store_code', 10)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
