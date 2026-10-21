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
        Schema::table('students', function (Blueprint $table) {
            // 1. Data Ayah Detail
            if (!Schema::hasColumn('students', 'father_nik')) {
                $table->string('father_nik', 50)->nullable()->after('father_name');
            }
            if (!Schema::hasColumn('students', 'father_birth_place')) {
                $table->string('father_birth_place', 100)->nullable()->after('father_nik');
            }
            if (!Schema::hasColumn('students', 'father_birth_date')) {
                $table->date('father_birth_date')->nullable()->after('father_birth_place');
            }
            if (!Schema::hasColumn('students', 'father_education')) {
                $table->string('father_education', 50)->nullable()->after('father_birth_date');
            }
            if (!Schema::hasColumn('students', 'father_company')) {
                $table->string('father_company', 150)->nullable()->after('father_job');
            }
            if (!Schema::hasColumn('students', 'father_income')) {
                $table->string('father_income', 50)->nullable()->after('father_company');
            }
            if (!Schema::hasColumn('students', 'father_title_prefix')) {
                $table->string('father_title_prefix', 30)->nullable()->after('father_income');
            }
            if (!Schema::hasColumn('students', 'father_title_suffix')) {
                $table->string('father_title_suffix', 30)->nullable()->after('father_title_prefix');
            }

            // 2. Data Ibu / Bunda Detail
            if (!Schema::hasColumn('students', 'mother_nik')) {
                $table->string('mother_nik', 50)->nullable()->after('mother_name');
            }
            if (!Schema::hasColumn('students', 'mother_birth_place')) {
                $table->string('mother_birth_place', 100)->nullable()->after('mother_nik');
            }
            if (!Schema::hasColumn('students', 'mother_birth_date')) {
                $table->date('mother_birth_date')->nullable()->after('mother_birth_place');
            }
            if (!Schema::hasColumn('students', 'mother_education')) {
                $table->string('mother_education', 50)->nullable()->after('mother_birth_date');
            }
            if (!Schema::hasColumn('students', 'mother_company')) {
                $table->string('mother_company', 150)->nullable()->after('mother_job');
            }
            if (!Schema::hasColumn('students', 'mother_income')) {
                $table->string('mother_income', 50)->nullable()->after('mother_company');
            }
            if (!Schema::hasColumn('students', 'mother_title_prefix')) {
                $table->string('mother_title_prefix', 30)->nullable()->after('mother_income');
            }
            if (!Schema::hasColumn('students', 'mother_title_suffix')) {
                $table->string('mother_title_suffix', 30)->nullable()->after('mother_title_prefix');
            }

            // 3. Data Wali Detail
            if (!Schema::hasColumn('students', 'guardian_nik')) {
                $table->string('guardian_nik', 50)->nullable()->after('guardian_name');
            }
            if (!Schema::hasColumn('students', 'guardian_birth_place')) {
                $table->string('guardian_birth_place', 100)->nullable()->after('guardian_nik');
            }
            if (!Schema::hasColumn('students', 'guardian_birth_date')) {
                $table->date('guardian_birth_date')->nullable()->after('guardian_birth_place');
            }
            if (!Schema::hasColumn('students', 'guardian_education')) {
                $table->string('guardian_education', 50)->nullable()->after('guardian_birth_date');
            }
            if (!Schema::hasColumn('students', 'guardian_job')) {
                $table->string('guardian_job', 100)->nullable()->after('guardian_education');
            }
            if (!Schema::hasColumn('students', 'guardian_company')) {
                $table->string('guardian_company', 150)->nullable()->after('guardian_job');
            }
            if (!Schema::hasColumn('students', 'guardian_income')) {
                $table->string('guardian_income', 50)->nullable()->after('guardian_company');
            }
            if (!Schema::hasColumn('students', 'guardian_relation')) {
                $table->string('guardian_relation', 50)->nullable()->after('guardian_income');
            }
            if (!Schema::hasColumn('students', 'is_accompanied_by_guardian')) {
                $table->boolean('is_accompanied_by_guardian')->default(false)->after('guardian_relation');
            }

            // 4. Kontak Darurat
            if (!Schema::hasColumn('students', 'emergency_contact_name')) {
                $table->string('emergency_contact_name', 100)->nullable()->after('parent_email');
            }
            if (!Schema::hasColumn('students', 'emergency_contact_phone')) {
                $table->string('emergency_contact_phone', 50)->nullable()->after('emergency_contact_name');
            }
            if (!Schema::hasColumn('students', 'emergency_contact_relation')) {
                $table->string('emergency_contact_relation', 50)->nullable()->after('emergency_contact_phone');
            }

            // 5. Riwayat Sekolah Sebelumnya, Cita-cita & Harapan Ortu
            if (!Schema::hasColumn('students', 'is_paud_formal_before')) {
                $table->boolean('is_paud_formal_before')->default(false)->after('previous_school');
            }
            if (!Schema::hasColumn('students', 'planned_school_after')) {
                $table->string('planned_school_after', 150)->nullable()->after('is_paud_formal_before');
            }
            if (!Schema::hasColumn('students', 'student_aspiration')) {
                $table->string('student_aspiration', 150)->nullable()->after('planned_school_after');
            }
            if (!Schema::hasColumn('students', 'student_hobby')) {
                $table->string('student_hobby', 150)->nullable()->after('student_aspiration');
            }
            if (!Schema::hasColumn('students', 'parent_aspiration')) {
                $table->text('parent_aspiration')->nullable()->after('student_aspiration');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $cols = [
                'father_nik', 'father_birth_place', 'father_birth_date', 'father_education',
                'father_company', 'father_income', 'father_title_prefix', 'father_title_suffix',
                'mother_nik', 'mother_birth_place', 'mother_birth_date', 'mother_education',
                'mother_company', 'mother_income', 'mother_title_prefix', 'mother_title_suffix',
                'guardian_nik', 'guardian_birth_place', 'guardian_birth_date', 'guardian_education',
                'guardian_job', 'guardian_company', 'guardian_income', 'guardian_relation',
                'is_accompanied_by_guardian', 'emergency_contact_name', 'emergency_contact_phone',
                'emergency_contact_relation', 'is_paud_formal_before', 'planned_school_after',
                'student_aspiration', 'student_hobby', 'parent_aspiration'
            ];

            foreach ($cols as $col) {
                if (Schema::hasColumn('students', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
