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
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('status_id')->constrained('statuses')->onDelete('restrict');
            $table->foreignId('driver_id')->nullable()->constrained('user_drivers')->onDelete('restrict');
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->onDelete('restrict');
            $table->foreignId('passenger_id')->constrained('user_passengers')->onDelete('restrict');
            $table->foreignId('revenue_id')->nullable()->constrained('revenues')->onDelete('restrict');
            $table->dateTime('start_trip')->nullable();
            $table->dateTime('end_trip')->nullable();
            $table->decimal('start_lat', 10, 8);
            $table->decimal('start_lng', 11, 8);
            $table->decimal('end_lat', 10, 8)->nullable();
            $table->decimal('end_lng', 11, 8)->nullable();
            $table->decimal('distance_km', 10, 2)->nullable();
            $table->decimal('average_speed_kmh', 6, 2)->nullable();
            $table->decimal('max_speed_kmh', 6, 2)->nullable();
            $table->text('route_path')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
