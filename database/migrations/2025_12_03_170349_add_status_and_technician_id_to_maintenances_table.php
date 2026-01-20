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
        Schema::table('maintenances', function (Blueprint $table) {
            $table->foreignId('status_id')
                ->default(6)
                ->after('vehicle_id')
                ->constrained('statuses')
                ->onDelete('restrict');

            $table->foreignId('technician_id')
                ->nullable()
                ->after('status_id')
                ->constrained('user_technicians')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropForeign(['technician_id']);

            $table->dropColumn(['status_id', 'technician_id']);
        });
    }
};
