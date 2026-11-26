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
        Schema::table('student_attendances', function (Blueprint $table) {
            // Drop foreign key temporarily to release index dependency in MySQL InnoDB
            $table->dropForeign('student_attendances_student_id_foreign');

            // Drop old unique constraint on [student_id, date]
            $table->dropUnique('student_attendances_student_id_date_unique');

            // Add composite unique constraint on [student_id, classroom_id, date]
            $table->unique(['student_id', 'classroom_id', 'date'], 'student_attendances_student_class_date_unique');

            // Restore foreign key constraint
            $table->foreign('student_id', 'student_attendances_student_id_foreign')
                ->references('id')
                ->on('students')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_attendances', function (Blueprint $table) {
            $table->dropForeign('student_attendances_student_id_foreign');
            $table->dropUnique('student_attendances_student_class_date_unique');
            $table->unique(['student_id', 'date'], 'student_attendances_student_id_date_unique');
            $table->foreign('student_id', 'student_attendances_student_id_foreign')
                ->references('id')
                ->on('students')
                ->onDelete('cascade');
        });
    }
};
