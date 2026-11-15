<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->enum('status', ['active', 'completed'])->default('active');
            $table->integer('total_orders')->default(0);
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->decimal('total_collected', 15, 2)->default(0)->comment('Total uang yang dikumpulkan');
            $table->time('started_at')->nullable();
            $table->time('ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date'], 'unique_delivery_per_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_assignments');
    }
};
