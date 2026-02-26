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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_bus_station_id')->constrained('bus_stations')->onDelete('cascade');
            $table->foreignId('to_bus_station_id')->constrained('bus_stations')->onDelete('cascade');
            $table->foreignId('passenger_id')->constrained('user_passengers')->onDelete('restrict');
            $table->foreignId('status_id')->constrained('statuses')->onDelete('restrict');
            $table->decimal('amount', 10, 2);
            $table->string('qrcode_name');
            $table->string('qrcode_img');
            $table->string('paymongo_checkout_session_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
