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
        Schema::create('station_amounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_bus_station_id')->constrained('bus_stations')->onDelete('cascade');
            $table->foreignId('to_bus_station_id')->constrained('bus_stations')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->unique(['from_bus_station_id', 'to_bus_station_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('station_amounts');
    }
};
