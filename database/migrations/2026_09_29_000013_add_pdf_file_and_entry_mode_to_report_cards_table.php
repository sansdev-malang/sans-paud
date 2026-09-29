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
        Schema::table('report_cards', function (Blueprint $table) {
            if (!Schema::hasColumn('report_cards', 'entry_mode')) {
                $table->enum('entry_mode', ['form', 'pdf'])->default('form')->after('semester')->comment('form = isi narasi digital, pdf = unggah berkas PDF langsung');
            }
            if (!Schema::hasColumn('report_cards', 'pdf_file')) {
                $table->string('pdf_file')->nullable()->after('photos')->comment('Path file berkas PDF rapor jika diunggah');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_cards', function (Blueprint $table) {
            $table->dropColumn(['entry_mode', 'pdf_file']);
        });
    }
};
