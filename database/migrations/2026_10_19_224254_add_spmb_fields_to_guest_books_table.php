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
        Schema::table('guest_books', function (Blueprint $table) {
            $table->string('student_name')->nullable()->after('phone');
            $table->date('student_birth_date')->nullable()->after('student_name');
            $table->string('therapy_history', 20)->nullable()->after('student_birth_date'); // 'Ya' / 'Tidak'
            $table->string('therapy_diagnosis')->nullable()->after('therapy_history');
            $table->text('purpose')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guest_books', function (Blueprint $table) {
            $table->dropColumn([
                'student_name',
                'student_birth_date',
                'therapy_history',
                'therapy_diagnosis',
            ]);
        });
    }
};
