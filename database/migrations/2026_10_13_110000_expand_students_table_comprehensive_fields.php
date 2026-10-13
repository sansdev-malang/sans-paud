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
            // 1. Identitas Lengkap & Dapodik
            if (!Schema::hasColumn('students', 'no_kk')) {
                $table->string('no_kk', 50)->nullable()->after('nik');
            }
            if (!Schema::hasColumn('students', 'no_akta')) {
                $table->string('no_akta', 50)->nullable()->after('no_kk');
            }
            if (!Schema::hasColumn('students', 'citizenship')) {
                $table->string('citizenship', 20)->default('WNI')->after('religion');
            }
            if (!Schema::hasColumn('students', 'rt')) {
                $table->string('rt', 10)->nullable()->after('address');
            }
            if (!Schema::hasColumn('students', 'rw')) {
                $table->string('rw', 10)->nullable()->after('rt');
            }
            if (!Schema::hasColumn('students', 'kelurahan')) {
                $table->string('kelurahan', 100)->nullable()->after('rw');
            }
            if (!Schema::hasColumn('students', 'kecamatan')) {
                $table->string('kecamatan', 100)->nullable()->after('kelurahan');
            }
            if (!Schema::hasColumn('students', 'postal_code')) {
                $table->string('postal_code', 10)->nullable()->after('city');
            }
            if (!Schema::hasColumn('students', 'living_with')) {
                $table->string('living_with', 50)->nullable()->after('postal_code');
            }
            if (!Schema::hasColumn('students', 'transportation')) {
                $table->string('transportation', 50)->nullable()->after('living_with');
            }
            if (!Schema::hasColumn('students', 'child_order')) {
                $table->unsignedTinyInteger('child_order')->nullable()->after('transportation');
            }
            if (!Schema::hasColumn('students', 'siblings_count')) {
                $table->unsignedTinyInteger('siblings_count')->nullable()->after('child_order');
            }
            if (!Schema::hasColumn('students', 'blood_type')) {
                $table->string('blood_type', 10)->nullable()->after('siblings_count');
            }
            if (!Schema::hasColumn('students', 'special_needs')) {
                $table->string('special_needs', 100)->nullable()->after('blood_type');
            }

            // 2. Fisik & Antropometri Awal
            if (!Schema::hasColumn('students', 'height')) {
                $table->decimal('height', 5, 2)->nullable()->after('special_needs');
            }
            if (!Schema::hasColumn('students', 'weight')) {
                $table->decimal('weight', 5, 2)->nullable()->after('height');
            }
            if (!Schema::hasColumn('students', 'head_circumference')) {
                $table->decimal('head_circumference', 5, 2)->nullable()->after('weight');
            }
            if (!Schema::hasColumn('students', 'arm_circumference')) {
                $table->decimal('arm_circumference', 5, 2)->nullable()->after('head_circumference');
            }
            if (!Schema::hasColumn('students', 'distance_to_school')) {
                $table->string('distance_to_school', 50)->nullable()->after('arm_circumference');
            }
            if (!Schema::hasColumn('students', 'travel_time_minutes')) {
                $table->integer('travel_time_minutes')->nullable()->after('distance_to_school');
            }

            // 3. Structured Data JSON untuk PAUD (Data Keluarga, Kebiasaan/Kesehatan, Psikologi/Milestones)
            if (!Schema::hasColumn('students', 'family_data')) {
                $table->json('family_data')->nullable()->after('parent_email');
            }
            if (!Schema::hasColumn('students', 'health_habits')) {
                $table->json('health_habits')->nullable()->after('family_data');
            }
            if (!Schema::hasColumn('students', 'development_psychology')) {
                $table->json('development_psychology')->nullable()->after('health_habits');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $columnsToDrop = [
                'no_kk', 'no_akta', 'citizenship', 'rt', 'rw', 'kelurahan', 'kecamatan',
                'postal_code', 'living_with', 'transportation', 'child_order', 'siblings_count',
                'blood_type', 'special_needs', 'height', 'weight', 'head_circumference',
                'arm_circumference', 'distance_to_school', 'travel_time_minutes',
                'family_data', 'health_habits', 'development_psychology'
            ];
            foreach ($columnsToDrop as $col) {
                if (Schema::hasColumn('students', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
