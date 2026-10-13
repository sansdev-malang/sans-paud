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
        Schema::create('classroom_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->onDelete('cascade');
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('date');
            $table->time('start_time')->nullable(); // e.g. 07:30
            $table->time('end_time')->nullable();   // e.g. 10:30
            $table->string('topic')->nullable();    // Tema / Topik Pembelajaran
            $table->text('class_condition');        // Uraian kegiatan & kondisi kelas
            $table->string('attachment_photo')->nullable(); // Foto dokumentasi KBM
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['classroom_id', 'date']);
            $table->index(['date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classroom_journals');
    }
};
