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
        Schema::create('boundary_contract_vehicle_type', function (Blueprint $table) {
            // composite primary key instead of auto-increment
            $table->foreignId('boundary_contract_id')->constrained('boundary_contracts')->onDelete('cascade');
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->onDelete('cascade');
            $table->foreignId('status_id')->constrained('statuses')->onDelete('restrict');
            $table->decimal('amount', 10, 2);

            // Set composite primary key
            $table->primary(['boundary_contract_id', 'vehicle_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boundary_contract_vehicle_type');
    }
};
