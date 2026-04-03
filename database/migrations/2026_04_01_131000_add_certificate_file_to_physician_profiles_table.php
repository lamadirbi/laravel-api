<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('physician_profiles', function (Blueprint $table) {
            $table->foreignId('certificate_file_id')
                ->nullable()
                ->constrained('medical_files')
                ->nullOnDelete()
                ->after('certificate');
        });
    }

    public function down(): void
    {
        Schema::table('physician_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('certificate_file_id');
        });
    }
};

