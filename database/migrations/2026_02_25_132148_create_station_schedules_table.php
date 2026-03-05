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
        Schema::create('station_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_reservation_id')->constrained('station_reservations')->onDelete('restrict');
            $table->foreignId('bus_station_id')->constrained('bus_stations')->onDelete('restrict');
            $table->integer('route_step');
            $table->time('from_time')->nullable();
            $table->time('to_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('station_schedules');
    }
};
