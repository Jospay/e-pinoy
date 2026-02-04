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
        Schema::create('user_driver_vehicle_type', function (Blueprint $table) {
            // composite primary key instead of auto-increment
            $table->foreignId('user_driver_id')->constrained('user_drivers')->onDelete('cascade');
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->onDelete('cascade');

            // Set composite primary key
            $table->primary(['user_driver_id', 'vehicle_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_driver_vehicle_type');
    }
};
