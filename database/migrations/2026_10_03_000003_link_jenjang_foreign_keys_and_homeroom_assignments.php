<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add jenjang_id & is_active to class_levels
        Schema::table('class_levels', function (Blueprint $table) {
            if (!Schema::hasColumn('class_levels', 'jenjang_id')) {
                $table->foreignId('jenjang_id')->nullable()->after('id')->constrained('jenjangs')->nullOnDelete();
            }
            if (!Schema::hasColumn('class_levels', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('order')->index();
            }
        });

        // 2. Add jenjang_id to classrooms
        Schema::table('classrooms', function (Blueprint $table) {
            if (!Schema::hasColumn('classrooms', 'jenjang_id')) {
                $table->foreignId('jenjang_id')->nullable()->after('id')->constrained('jenjangs')->nullOnDelete();
            }
        });

        // 3. Add jenjang_id to students
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'jenjang_id')) {
                $table->foreignId('jenjang_id')->nullable()->after('academic_year_id')->constrained('jenjangs')->nullOnDelete();
            }
        });

        // 4. Create homeroom_assignments table for tracking Wali Kelas per Tahun Pelajaran
        if (!Schema::hasTable('homeroom_assignments')) {
            Schema::create('homeroom_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->foreignId('jenjang_id')->nullable()->constrained('jenjangs')->nullOnDelete();
                $table->foreignId('class_level_id')->nullable()->constrained('class_levels')->nullOnDelete();
                $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->boolean('is_active')->default(true)->index();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['academic_year_id', 'classroom_id', 'employee_id'], 'unique_tapel_rombel_teacher');
            });
        }

        // 5. Backfill jenjang_id on class_levels, classrooms, students
        $kbJenjang = DB::table('jenjangs')->where('code', 'KB')->first();
        $tkJenjang = DB::table('jenjangs')->where('code', 'TK')->first();
        $daycareJenjang = DB::table('jenjangs')->where('code', 'DAYCARE')->first();
        $tpqJenjang = DB::table('jenjangs')->where('code', 'TPQ')->first();

        if ($kbJenjang) {
            DB::table('class_levels')->whereIn('sub_unit', ['PG', 'KB'])->orWhere('name', 'LIKE', '%KB%')->update(['jenjang_id' => $kbJenjang->id]);
        }
        if ($tkJenjang) {
            DB::table('class_levels')->where('sub_unit', 'TK')->orWhere('name', 'LIKE', '%TK%')->update(['jenjang_id' => $tkJenjang->id]);
        }
        if ($daycareJenjang) {
            DB::table('class_levels')->whereIn('sub_unit', ['DAYCARE', 'TPA'])->orWhere('name', 'LIKE', '%TPA%')->update(['jenjang_id' => $daycareJenjang->id]);
        }
        if ($tpqJenjang) {
            DB::table('class_levels')->where('sub_unit', 'TPQ')->orWhere('name', 'LIKE', '%TPQ%')->update(['jenjang_id' => $tpqJenjang->id]);
        }

        // Backfill classrooms from class_levels
        $classLevels = DB::table('class_levels')->get();
        foreach ($classLevels as $lvl) {
            if ($lvl->jenjang_id) {
                DB::table('classrooms')->where('class_level_id', $lvl->id)->update(['jenjang_id' => $lvl->jenjang_id]);
                DB::table('students')->where('class_level_id', $lvl->id)->update(['jenjang_id' => $lvl->jenjang_id]);
            }
        }

        // Also backfill classrooms by sub_unit if still null
        if ($kbJenjang) {
            DB::table('classrooms')->whereNull('jenjang_id')->whereIn('sub_unit', ['PG', 'KB'])->update(['jenjang_id' => $kbJenjang->id]);
            DB::table('students')->whereNull('jenjang_id')->whereIn('sub_unit', ['PG', 'KB'])->update(['jenjang_id' => $kbJenjang->id]);
        }
        if ($tkJenjang) {
            DB::table('classrooms')->whereNull('jenjang_id')->where('sub_unit', 'TK')->update(['jenjang_id' => $tkJenjang->id]);
            DB::table('students')->whereNull('jenjang_id')->where('sub_unit', 'TK')->update(['jenjang_id' => $tkJenjang->id]);
        }
        if ($daycareJenjang) {
            DB::table('classrooms')->whereNull('jenjang_id')->whereIn('sub_unit', ['DAYCARE', 'TPA'])->update(['jenjang_id' => $daycareJenjang->id]);
            DB::table('students')->whereNull('jenjang_id')->whereIn('sub_unit', ['DAYCARE', 'TPA'])->update(['jenjang_id' => $daycareJenjang->id]);
        }
        if ($tpqJenjang) {
            DB::table('classrooms')->whereNull('jenjang_id')->where('sub_unit', 'TPQ')->update(['jenjang_id' => $tpqJenjang->id]);
            DB::table('students')->whereNull('jenjang_id')->where('sub_unit', 'TPQ')->update(['jenjang_id' => $tpqJenjang->id]);
        }

        // Backfill homeroom_assignments from existing classrooms
        $activeYear = DB::table('academic_years')->where('is_active', true)->first();
        if ($activeYear) {
            $classroomsWithTeachers = DB::table('classrooms')
                ->whereNotNull('homeroom_teacher_id')
                ->get();

            $now = now();
            foreach ($classroomsWithTeachers as $c) {
                $yearId = $c->academic_year_id ?: $activeYear->id;
                $exists = DB::table('homeroom_assignments')
                    ->where('academic_year_id', $yearId)
                    ->where('classroom_id', $c->id)
                    ->where('employee_id', $c->homeroom_teacher_id)
                    ->exists();

                if (!$exists) {
                    DB::table('homeroom_assignments')->insert([
                        'academic_year_id' => $yearId,
                        'jenjang_id' => $c->jenjang_id,
                        'class_level_id' => $c->class_level_id,
                        'classroom_id' => $c->id,
                        'employee_id' => $c->homeroom_teacher_id,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homeroom_assignments');

        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['jenjang_id']);
            $table->dropColumn(['jenjang_id']);
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropForeign(['jenjang_id']);
            $table->dropColumn(['jenjang_id']);
        });

        Schema::table('class_levels', function (Blueprint $table) {
            $table->dropForeign(['jenjang_id']);
            $table->dropColumn(['jenjang_id', 'is_active']);
        });
    }
};
