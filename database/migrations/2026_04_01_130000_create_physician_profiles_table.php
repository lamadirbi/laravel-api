<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('physician_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->unique();
            $table->string('specialty', 255);
            $table->text('certificate');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('physician_profiles');
    }
};

