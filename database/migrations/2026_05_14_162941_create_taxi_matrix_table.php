<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('taxi_matrix', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('flag_rate')->nullable();
            $table->bigInteger('per_minute')->nullable();
            $table->bigInteger('per_km')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxi_matrix');
    }
};
