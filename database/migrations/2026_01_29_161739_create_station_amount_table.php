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
        Schema::create('station_amount', function (Blueprint $table) {
        $table->id();
        $table->foreignId('first_bus_station_id')->constrained('bus_stations')->onDelete('cascade');
        $table->foreignId('second_bus_station_id')->constrained('bus_stations')->onDelete('cascade');
        $table->decimal('amount', 10, 2);
        $table->unique(['first_bus_station_id', 'second_bus_station_id']);
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('station_amount');
    }
};
