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
        Schema::create('spmb_candidates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('spmb_registration_id')->nullable()->index();
            $table->string('registration_number')->unique();
            $table->string('academic_year')->nullable()->index(); // e.g. 2026/2027
            $table->string('unit_code')->nullable()->index();     // e.g. PAUD
            $table->string('unit_name')->nullable();
            $table->string('wave')->nullable();                    // e.g. Gelombang 1
            $table->string('class_program')->nullable();            // e.g. Reguler
            
            // Biodata Calon Siswa
            $table->string('full_name');
            $table->string('nickname')->nullable();
            $table->string('nik')->nullable()->index();
            $table->string('nisn')->nullable()->index();
            $table->string('gender')->nullable();                  // L / P
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('religion')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('previous_school')->nullable();
            
            // Data Orang Tua / Kontak
            $table->string('father_name')->nullable();
            $table->string('father_phone')->nullable();
            $table->string('father_job')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('mother_phone')->nullable();
            $table->string('mother_job')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->string('parent_phone')->nullable();            // Primary WhatsApp
            $table->string('parent_email')->nullable();
            
            // Status & Finansial
            $table->string('registration_status')->default('verified'); // verified, accepted, pending
            $table->string('payment_status')->default('unpaid');        // paid, unpaid, partial
            $table->timestamp('verified_at')->nullable();
            
            // Berkas & Media
            $table->text('student_photo_url')->nullable();
            $table->json('documents')->nullable();
            $table->json('payments')->nullable();
            $table->json('raw_payload')->nullable();               // Full SPMB payload JSON
            
            // Status Siswa Aktif di PAUD
            $table->boolean('is_enrolled')->default(false)->index();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spmb_candidates');
    }
};
