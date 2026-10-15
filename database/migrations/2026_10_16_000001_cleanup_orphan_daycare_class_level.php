<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clean up legacy orphan class level 'Daycare' that has no jenjang_id and no relations
        $orphan = DB::table('class_levels')
            ->where('name', 'Daycare')
            ->whereNull('jenjang_id')
            ->first();

        if ($orphan) {
            $hasClassroom = DB::table('classrooms')->where('class_level_id', $orphan->id)->exists();
            $hasStudent = DB::table('students')->where('class_level_id', $orphan->id)->exists();
            $hasHomeroom = DB::table('homeroom_assignments')->where('class_level_id', $orphan->id)->exists();

            if (!$hasClassroom && !$hasStudent && !$hasHomeroom) {
                DB::table('class_levels')->where('id', $orphan->id)->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse operation needed for orphan cleanup
    }
};
