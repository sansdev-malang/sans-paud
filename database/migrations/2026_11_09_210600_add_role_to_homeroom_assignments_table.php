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
        Schema::table('homeroom_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('homeroom_assignments', 'role')) {
                $table->string('role')->default('wali_kelas')->after('employee_id')->index();
            }
            if (!Schema::hasColumn('homeroom_assignments', 'subject_name')) {
                $table->string('subject_name')->nullable()->after('role');
            }
            // Allow classroom_id to be nullable for koordinator_tingkat
            $table->unsignedBigInteger('classroom_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('homeroom_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('homeroom_assignments', 'subject_name')) {
                $table->dropColumn('subject_name');
            }
            if (Schema::hasColumn('homeroom_assignments', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};

