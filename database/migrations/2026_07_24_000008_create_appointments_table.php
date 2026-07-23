<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_bay_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('mechanic_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status')->default('scheduled');  // enum-backed in model
            $table->text('customer_notes')->nullable();
            $table->text('staff_notes')->nullable();
            $table->timestamps();

            // Helps the overlap query run fast
            $table->index(['service_bay_id', 'starts_at', 'ends_at']);
            $table->index(['mechanic_id', 'starts_at', 'ends_at']);
            $table->index('starts_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
