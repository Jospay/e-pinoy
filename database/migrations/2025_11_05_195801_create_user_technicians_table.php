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
        Schema::create('user_technicians', function (Blueprint $table) {
            $table->foreignId('id')->primary()->constrained('users')->onDelete('cascade');
            $table->foreignId('status_id')->constrained('statuses')->onDelete('restrict');
            $table->enum('expertise', ['Mechanical', 'Electrical', 'Battery' ])->default('Mechanical');
            $table->unsignedTinyInteger('year_experience');
            $table->string('certificate_prc_no')->nullable();
            $table->string('professional_license')->nullable();
            $table->enum('valid_id_type', ['National ID', 'Passport', 'Driver License', 'Voter ID', 'Unified Multi-Purpose ID', 'TIN ID'])->default('National ID');
            $table->string('valid_id_number', 20)->unique();
            $table->string('front_valid_id_picture');
            $table->string('back_valid_id_picture');
            $table->string('cv_attachment');
            $table->date('birth_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_technicians');
    }
};
