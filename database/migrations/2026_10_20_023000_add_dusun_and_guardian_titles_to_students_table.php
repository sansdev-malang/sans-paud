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
            if (!Schema::hasColumn('students', 'dusun')) {
                $table->string('dusun', 100)->nullable()->after('address');
            }
            if (!Schema::hasColumn('students', 'guardian_title_prefix')) {
                $table->string('guardian_title_prefix', 150)->nullable()->after('guardian_income');
            }
            if (!Schema::hasColumn('students', 'guardian_title_suffix')) {
                $table->string('guardian_title_suffix', 150)->nullable()->after('guardian_title_prefix');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'dusun')) {
                $table->dropColumn('dusun');
            }
            if (Schema::hasColumn('students', 'guardian_title_prefix')) {
                $table->dropColumn('guardian_title_prefix');
            }
            if (Schema::hasColumn('students', 'guardian_title_suffix')) {
                $table->dropColumn('guardian_title_suffix');
            }
        });
    }
};
