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
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('physician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('caregiver_id')->nullable()->constrained('users')->nullOnDelete();

            $table->longText('question_text');
            $table->enum('status', ['pending', 'completed'])->default('pending')->index();

            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamp('responded_at')->nullable();

            $table->longText('physician_response')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
