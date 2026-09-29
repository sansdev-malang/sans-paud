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
        // 1. Update class_levels
        Schema::table('class_levels', function (Blueprint $table) {
            if (!Schema::hasColumn('class_levels', 'sub_unit')) {
                $table->string('sub_unit')->nullable()->after('name')->comment('PG, TK, DAYCARE, TPQ');
            }
        });

        // 2. Update classrooms
        Schema::table('classrooms', function (Blueprint $table) {
            if (!Schema::hasColumn('classrooms', 'sub_unit')) {
                $table->string('sub_unit')->nullable()->after('class_level_id')->comment('PG, TK, DAYCARE, TPQ');
            }
        });

        // 3. Update students
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'sub_unit')) {
                $table->string('sub_unit')->nullable()->after('academic_year_id')->comment('PG, TK, DAYCARE, TPQ');
            }
            if (!Schema::hasColumn('students', 'class_level_id')) {
                $table->foreignId('class_level_id')->nullable()->after('academic_year_id')->constrained('class_levels')->nullOnDelete();
            }
            if (!Schema::hasColumn('students', 'daycare_classroom_id')) {
                $table->foreignId('daycare_classroom_id')->nullable()->after('classroom_id')->constrained('classrooms')->nullOnDelete();
            }
            if (!Schema::hasColumn('students', 'is_tpq')) {
                $table->boolean('is_tpq')->default(false)->after('daycare_classroom_id');
            }
            if (!Schema::hasColumn('students', 'tpq_classroom_id')) {
                $table->foreignId('tpq_classroom_id')->nullable()->after('is_tpq')->constrained('classrooms')->nullOnDelete();
            }
            if (!Schema::hasColumn('students', 'pin_access')) {
                $table->string('pin_access')->nullable()->after('parent_email')->comment('PIN akses portal e-raport ortu');
            }
        });

        // 4. Create student_classroom_histories
        if (!Schema::hasTable('student_classroom_histories')) {
            Schema::create('student_classroom_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
                $table->string('sub_unit')->nullable();
                $table->string('grade_level')->nullable();
                $table->string('classroom_name')->nullable();
                $table->string('homeroom_teacher_name')->nullable();
                $table->enum('status', ['aktif', 'lulus', 'mutasi', 'keluar', 'tinggal_kelas'])->default('aktif');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['student_id', 'academic_year_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_classroom_histories');

        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['class_level_id']);
            $table->dropForeign(['daycare_classroom_id']);
            $table->dropForeign(['tpq_classroom_id']);
            $table->dropColumn(['sub_unit', 'class_level_id', 'daycare_classroom_id', 'is_tpq', 'tpq_classroom_id', 'pin_access']);
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropColumn(['sub_unit']);
        });

        Schema::table('class_levels', function (Blueprint $table) {
            $table->dropColumn(['sub_unit']);
        });
    }
};
