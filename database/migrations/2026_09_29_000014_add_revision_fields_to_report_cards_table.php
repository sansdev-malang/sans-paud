<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('report_cards')) {
            // Modify enum for status in MySQL to include 'revision' and 'rejected'
            DB::statement("ALTER TABLE `report_cards` MODIFY COLUMN `status` ENUM('draft', 'submitted', 'revision', 'rejected', 'approved', 'published') NOT NULL DEFAULT 'draft'");

            Schema::table('report_cards', function (Blueprint $table) {
                if (!Schema::hasColumn('report_cards', 'revision_notes')) {
                    $table->text('revision_notes')->nullable()->after('teacher_notes')->comment('Catatan perbaikan / revisi dari Kepala Sekolah');
                }
                if (!Schema::hasColumn('report_cards', 'rejected_by')) {
                    $table->foreignId('rejected_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('report_cards', 'rejected_at')) {
                    $table->timestamp('rejected_at')->nullable()->after('rejected_by');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('report_cards')) {
            Schema::table('report_cards', function (Blueprint $table) {
                if (Schema::hasColumn('report_cards', 'rejected_by')) {
                    $table->dropForeign(['rejected_by']);
                    $table->dropColumn('rejected_by');
                }
                if (Schema::hasColumn('report_cards', 'rejected_at')) {
                    $table->dropColumn('rejected_at');
                }
                if (Schema::hasColumn('report_cards', 'revision_notes')) {
                    $table->dropColumn('revision_notes');
                }
            });

            DB::statement("ALTER TABLE `report_cards` MODIFY COLUMN `status` ENUM('draft', 'submitted', 'approved', 'published') NOT NULL DEFAULT 'draft'");
        }
    }
};
