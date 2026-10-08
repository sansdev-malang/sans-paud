<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('spmb_candidates') && Schema::hasColumn('spmb_candidates', 'academic_year')) {
            // Normalize any hyphen format '2026-2027' into standard slash format '2026/2027'
            DB::table('spmb_candidates')
                ->where('academic_year', 'like', '%-%')
                ->chunkById(100, function ($candidates) {
                    foreach ($candidates as $candidate) {
                        $normalized = str_replace('-', '/', $candidate->academic_year);
                        DB::table('spmb_candidates')
                            ->where('id', $candidate->id)
                            ->update(['academic_year' => $normalized]);
                    }
                });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed as slash is the standard format
    }
};
