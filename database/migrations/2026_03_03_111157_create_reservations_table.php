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
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('restrict');
            $table->foreignId('from_bus_station_id')->constrained('bus_stations')->onDelete('cascade');
            $table->foreignId('to_bus_station_id')->constrained('bus_stations')->onDelete('cascade');
            $table->foreignId('passenger_id')->constrained('user_passengers')->onDelete('restrict');
            $table->foreignId('status_id')->constrained('statuses')->onDelete('restrict');
            $table->integer('passenger_count');
            $table->decimal('amount', 10, 2);
            $table->time('reserve_from_time');
            $table->time('reserve_to_time');
            $table->date('reserve_date');
            $table->string('qrcode_name');
            $table->string('payment_options');
            $table->string('paymongo_checkout_session_id')->nullable();
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
