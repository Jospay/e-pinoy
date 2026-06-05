<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('qr_transactions', function (Blueprint $table) {
            $table->id();

            $table->string('reference', 100)->nullable();
            $table->string('qr_id', 100)->nullable();

            $table->string('status', 20)
                ->default('pending');

            $table->integer('amount')
                ->default(0);

            $table->timestamp('created_at')
                ->useCurrent();

            $table->timestamp('updated_at')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_transactions');
    }
};
