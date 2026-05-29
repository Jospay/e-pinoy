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
        Schema::create('tricycle_terminals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('user_owners')->onDelete('restrict');
            $table->foreignId('manager_id')->nullable()->constrained('user_managers')->onDelete('restrict');
            $table->foreignId('status_id')->constrained('statuses')->onDelete('restrict');
            $table->string('name')->unique();
            $table->string('region');
            $table->string('province')->nullable();
            $table->string('city');
            $table->string('barangay');
            $table->string('street');
            $table->string('postal_code', 20);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tricycle_terminals');
    }
};
