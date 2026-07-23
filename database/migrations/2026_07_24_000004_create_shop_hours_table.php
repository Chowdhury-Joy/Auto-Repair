<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shop_hours', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('day_of_week');     // 1=Mon ... 7=Sun (ISO-8601)
            $table->time('opens_at')->nullable();   // null = closed
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->unique('day_of_week');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_hours');
    }
};
