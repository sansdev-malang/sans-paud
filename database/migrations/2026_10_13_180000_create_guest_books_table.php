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
        Schema::create('guest_books', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // Nama Tamu
            $table->string('phone');                // No HP / WhatsApp
            $table->string('category', 50);         // Kategori: Umum, Dinas, Puskesmas/Dinkes, SPMB
            $table->string('institution')->nullable(); // Institusi / Instansi
            $table->string('position')->nullable();    // Jabatan
            $table->string('occupation')->nullable();  // Pekerjaan
            $table->text('address')->nullable();       // Alamat
            $table->string('city')->nullable();        // Kota / Kabupaten
            $table->string('province')->nullable();    // Provinsi
            $table->text('purpose');                   // Keperluan
            $table->string('host_recipient')->nullable(); // Penerima Tamu / Yang Ditemui
            $table->text('feedback')->nullable();      // Kesan & Pesan
            $table->date('date');                      // Tanggal Kunjungan
            $table->time('time')->nullable();          // Waktu / Jam Datang
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['date']);
            $table->index(['category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_books');
    }
};
