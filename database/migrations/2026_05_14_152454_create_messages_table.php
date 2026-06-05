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
        Schema::create('messages', function (Blueprint $table) {
            $table->id('message_id');

            $table->unsignedBigInteger('room_id')->nullable();
            $table->unsignedBigInteger('sender_id')->nullable();

            $table->string('sender_type', 20)->nullable();
            $table->string('username', 100)->nullable();
            $table->string('gender', 10)->nullable();

            $table->text('avatar_url')->nullable();
            $table->text('message')->nullable();

            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
