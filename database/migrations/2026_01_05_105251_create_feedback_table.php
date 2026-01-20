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
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('user_type_id')->constrained('user_types')->onDelete('restrict');
            $table->string('avatar')->nullable();
            $table->decimal('rating', 2, 1)->comment('Star rating from 1.0 to 5.0 (0.5 increments)');
            $table->text('description');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
