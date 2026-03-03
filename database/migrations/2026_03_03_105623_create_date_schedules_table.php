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
        Schema::create('date_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_reservation_id')->constrained('station_reservations')->onDelete('restrict');
            $table->foreignId('day_schedule_id')->constrained('day_schedules')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('date_schedules');
    }
};
