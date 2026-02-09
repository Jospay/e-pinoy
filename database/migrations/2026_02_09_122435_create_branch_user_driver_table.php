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
        Schema::create('branch_user_driver', function (Blueprint $table) {
            // composite primary key instead of auto-increment
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('user_driver_id')->constrained('user_drivers')->onDelete('cascade');

            // Set composite primary key
            $table->primary(['branch_id', 'user_driver_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_user_driver');
    }
};
