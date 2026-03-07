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
        Schema::create('taxi_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->onDelete('restrict');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('restrict');
            $table->foreignId('passenger_id')->constrained('user_passengers')->onDelete('restrict');
            $table->foreignId('status_id')->constrained('statuses')->onDelete('restrict');
            $table->integer('passenger_count');
            $table->decimal('amount', 10, 2);
            $table->string('pickup_loc_name')->nullable();
            $table->string('destination_loc_name')->nullable();
            $table->decimal('start_lat', 10, 8);
            $table->decimal('start_lng', 11, 8);
            $table->decimal('end_lat', 10, 8)->nullable();
            $table->decimal('end_lng', 11, 8)->nullable();
            $table->decimal('distance_km', 10, 2)->nullable();
            $table->decimal('average_speed_kmh', 6, 2)->nullable();
            $table->decimal('max_speed_kmh', 6, 2)->nullable();
            $table->text('route_path')->nullable();
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
        Schema::dropIfExists('taxi_reservations');
    }
};
