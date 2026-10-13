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
        Schema::create('student_ddtk_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->onDelete('cascade');
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->string('period', 20)->index(); // e.g. '2026-10' or '2026-Smt-1'
            $table->date('date'); // tanggal pemeriksaan

            // DDTK (Pertumbuhan / Antropometri)
            $table->decimal('height', 5, 2)->nullable(); // Tinggi Badan (cm)
            $table->decimal('weight', 5, 2)->nullable(); // Berat Badan (kg)
            $table->decimal('head_circumference', 5, 2)->nullable(); // Lingkar Kepala (cm)
            $table->decimal('arm_circumference', 5, 2)->nullable(); // Lingkar Lengan (cm)

            // SDIDTK (Skrining Perkembangan & Medis UKS)
            $table->string('visus_right', 50)->nullable(); // Visus Mata Kanan
            $table->string('visus_left', 50)->nullable(); // Visus Mata Kiri
            $table->string('kpsp_result', 100)->nullable(); // KPSP (Sesuai / Meragukan / Penyimpangan)
            $table->string('dental_health', 150)->nullable(); // Gigi & Mulut
            $table->decimal('blood_sugar', 5, 1)->nullable(); // Gula Darah Acak (GDA mg/dL)
            $table->boolean('has_leprosy')->default(false); // Gejala Kusta (true: Ya, false: Tidak)
            $table->boolean('has_frambesia')->default(false); // Gejala Frambusia (true: Ya, false: Tidak)
            $table->text('notes')->nullable(); // Catatan

            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'period']);
            $table->index(['classroom_id', 'period']);
            $table->index(['date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_ddtk_records');
    }
};
