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
        // 1. Bank Template Narasi & Tujuan Pembelajaran (TP) PAUD
        if (!Schema::hasTable('learning_objectives')) {
            Schema::create('learning_objectives', function (Blueprint $table) {
                $table->id();
                $table->string('category')->comment('NABP, JATI_DIRI, STEAM, P5, DAYCARE, TPQ, TEACHER_NOTES');
                $table->string('sub_unit')->default('ALL')->comment('PG, TK, DAYCARE, TPQ, ALL');
                $table->string('grade_level')->default('ALL')->comment('KB-A, KB-B, TK-A, TK-B, ALL');
                $table->string('title');
                $table->text('sample_narrative');
                $table->integer('order')->default(0);
                $table->timestamps();
            });
        }

        // 2. Rapor Siswa PAUD (KB, TK, Daycare & TPQ)
        if (!Schema::hasTable('report_cards')) {
            Schema::create('report_cards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
                $table->enum('semester', ['1', '2', 'Ganjil', 'Genap'])->default('1');
                $table->date('report_date')->nullable();
                $table->string('place')->default('Malang');

                // Snapshots for official print
                $table->string('homeroom_teacher_name')->nullable();
                $table->string('principal_name')->nullable();

                // Pertumbuhan Fisik & Kesehatan
                $table->decimal('height', 5, 2)->nullable()->comment('Tinggi Badan (cm)');
                $table->decimal('weight', 5, 2)->nullable()->comment('Berat Badan (kg)');
                $table->decimal('head_circumference', 5, 2)->nullable()->comment('Lingkar Kepala (cm)');

                // Rekap Ketidakhadiran
                $table->unsignedSmallInteger('attendance_sick')->default(0);
                $table->unsignedSmallInteger('attendance_permission')->default(0);
                $table->unsignedSmallInteger('attendance_unexcused')->default(0);

                // Elemen Capaian Pembelajaran (CP) Kurikulum Merdeka PAUD
                $table->text('nabp_narrative')->nullable()->comment('Nilai Agama dan Budi Pekerti');
                $table->text('jati_diri_narrative')->nullable()->comment('Jati Diri, Sosial Emosional & Motorik');
                $table->text('steam_narrative')->nullable()->comment('Dasar-dasar Literasi, Matematika, Sains, STEAM');

                // Projek Penguatan Profil Pelajar Pancasila (P5)
                $table->string('p5_project_name')->nullable()->comment('Tema / Judul Projek P5');
                $table->text('p5_narrative')->nullable()->comment('Narasi Capaian Projek P5');

                // Layanan Tambahan: Daycare & TPQ
                $table->text('daycare_narrative')->nullable()->comment('Catatan Tumbuh Kembang Daycare/TPA');
                $table->string('tpq_jilid')->nullable()->comment('Capaian Jilid Mengaji (Tilawati/Ummi/Iqro)');
                $table->string('tpq_surah')->nullable()->comment('Hafalan Surat Pendek');
                $table->string('tpq_hadith_doa')->nullable()->comment('Hafalan Hadits & Doa Harian');
                $table->text('tpq_narrative')->nullable()->comment('Catatan & Evaluasi Guru TPQ');

                // Refleksi & Catatan
                $table->text('teacher_notes')->nullable()->comment('Pesan & Motivasi Wali Kelas untuk Ananda & Ortu');
                $table->text('parent_feedback')->nullable()->comment('Catatan Umpan Balik Orang Tua');

                // Dokumentasi Foto Kegiatan
                $table->json('photos')->nullable()->comment('Array path foto kegiatan ananda');

                // Status Alur Rapor
                $table->enum('status', ['draft', 'submitted', 'approved', 'published'])->default('draft');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();

                $table->timestamps();

                $table->unique(['student_id', 'academic_year_id', 'semester'], 'unique_student_report_semester');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_cards');
        Schema::dropIfExists('learning_objectives');
    }
};
