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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('status_id')->constrained('statuses')->onDelete('restrict');
            $table->foreignId('franchise_id')->nullable()->constrained('franchises')->onDelete('restrict');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('restrict');
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->onDelete('restrict');
            $table->foreignId('driver_id')->nullable()->constrained('user_drivers')->onDelete('restrict');
            $table->string('plate_number', 20)->unique();
            $table->string('vin', 50)->unique();
            $table->unsignedTinyInteger('capacity');
            $table->string('brand', 100);
            $table->string('model', 100);
            $table->year('year');
            $table->string('color', 50);
            $table->string('or_cr');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
