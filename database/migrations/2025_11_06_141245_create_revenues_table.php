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
        Schema::create('revenues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('status_id')->constrained('statuses')->onDelete('restrict');
            $table->foreignId('franchise_id')->nullable()->constrained('franchises')->onDelete('restrict');
            $table->foreignId('driver_id')->nullable()->constrained('user_drivers')->onDelete('restrict');
            $table->foreignId('boundary_contract_id')->nullable()->constrained('boundary_contracts')->onDelete('restrict');
            $table->foreignId('payment_option_id')->nullable()->constrained('payment_options')->onDelete('restrict');
            $table->string('invoice_no', 100)->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('PHP');
            $table->enum('service_type', ['Trips','Boundary'])->default('Trips');
            $table->dateTime('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenues');
    }
};
