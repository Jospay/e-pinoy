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
        Schema::create('violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('status_id')->constrained('statuses')->onDelete('restrict');
            $table->foreignId('franchise_id')->nullable()->constrained('franchises')->onDelete('restrict');
            $table->foreignId('driver_id')->constrained('user_drivers')->onDelete('restrict');
            $table->enum('violation_type', ['Speeding', 'Reckless Driving', 'No Seatbelt', 'Expired License', 'Illegal Parking', 'Other'])->default('Other');
            $table->text('description');
            $table->date('violation_date');
            $table->decimal('fine_amount', 10, 2);
            $table->string('currency', 10)->default('PHP');
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('violations');
    }
};
