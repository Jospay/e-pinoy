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
        Schema::table('user_drivers', function (Blueprint $table) {
            $table->foreignId('tricycle_terminal_id')
                  ->after('status_id')
                  ->nullable()
                  ->constrained('tricycle_terminals')
                  ->onDelete('restrict');
        });

        Schema::table('boundary_contracts', function (Blueprint $table) {
            $table->foreignId('tricycle_terminal_id')
                  ->after('driver_id')
                  ->nullable()
                  ->constrained('tricycle_terminals')
                  ->onDelete('restrict');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->foreignId('tricycle_terminal_id')
                  ->after('driver_id')
                  ->nullable()
                  ->constrained('tricycle_terminals')
                  ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_drivers', function (Blueprint $table) {
            $table->dropForeign(['tricycle_terminal_id']);
            $table->dropColumn('tricycle_terminal_id');
        });

        Schema::table('boundary_contracts', function (Blueprint $table) {
            $table->dropForeign(['tricycle_terminal_id']);
            $table->dropColumn('tricycle_terminal_id');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign(['tricycle_terminal_id']);
            $table->dropColumn('tricycle_terminal_id');
        });
    }
};
