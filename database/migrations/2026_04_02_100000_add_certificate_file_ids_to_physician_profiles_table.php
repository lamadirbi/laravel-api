<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('physician_profiles', function (Blueprint $table) {
            $table->json('certificate_file_ids')->nullable();
        });

        $rows = DB::table('physician_profiles')
            ->select('id', 'certificate_file_id')
            ->whereNotNull('certificate_file_id')
            ->get();

        foreach ($rows as $row) {
            DB::table('physician_profiles')
                ->where('id', $row->id)
                ->update(['certificate_file_ids' => json_encode([(int) $row->certificate_file_id])]);
        }
    }

    public function down(): void
    {
        Schema::table('physician_profiles', function (Blueprint $table) {
            $table->dropColumn('certificate_file_ids');
        });
    }
};
