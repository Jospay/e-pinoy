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
        Schema::create('franchise_user_technician', function (Blueprint $table) {
            // composite primary key instead of auto-increment
            $table->foreignId('franchise_id')->constrained('franchises')->onDelete('cascade');
            $table->foreignId('user_technician_id')->constrained('user_technicians')->onDelete('cascade');

            // Set composite primary key
            $table->primary(['franchise_id', 'user_technician_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('franchise_technician');
    }
};
