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
            $table->index('sub_unit');
            $table->index('gender');
            $table->index(['academic_year_id', 'status']);
            $table->index(['classroom_id', 'status']);
            $table->index(['daycare_classroom_id', 'status']);
            $table->index(['tpq_classroom_id', 'status']);
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->index('sub_unit');
            $table->index('is_active');
            $table->index(['academic_year_id', 'is_active']);
        });

        if (Schema::hasTable('report_cards')) {
            Schema::table('report_cards', function (Blueprint $table) {
                $table->index(['academic_year_id', 'semester', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['sub_unit']);
            $table->dropIndex(['gender']);
            $table->dropIndex(['academic_year_id', 'status']);
            $table->dropIndex(['classroom_id', 'status']);
            $table->dropIndex(['daycare_classroom_id', 'status']);
            $table->dropIndex(['tpq_classroom_id', 'status']);
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropIndex(['sub_unit']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['academic_year_id', 'is_active']);
        });

        if (Schema::hasTable('report_cards')) {
            Schema::table('report_cards', function (Blueprint $table) {
                $table->dropIndex(['academic_year_id', 'semester', 'status']);
            });
        }
    }
};
