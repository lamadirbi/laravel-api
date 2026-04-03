<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('consultations') && Schema::hasColumn('consultations', 'caregiver_id')) {
            Schema::table('consultations', function (Blueprint $table) {
                $table->dropConstrainedForeignId('caregiver_id');
            });
        }

        Schema::dropIfExists('caregiver_invites');
        Schema::dropIfExists('caregiver_patient');
    }

    public function down(): void
    {
        Schema::create('caregiver_patient', function (Blueprint $table) {
            $table->foreignId('caregiver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['caregiver_id', 'patient_id']);
            $table->timestamps();
        });

        Schema::create('caregiver_invites', function (Blueprint $table) {
            $table->id();
            $table->uuid('code')->unique();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('used_at')->nullable()->index();
            $table->foreignId('used_by_caregiver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        if (Schema::hasTable('consultations') && !Schema::hasColumn('consultations', 'caregiver_id')) {
            Schema::table('consultations', function (Blueprint $table) {
                $table->foreignId('caregiver_id')->nullable()->constrained('users')->nullOnDelete();
            });
        }
    }
};

