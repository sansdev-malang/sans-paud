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
            $table->string('father_title_prefix', 150)->nullable()->change();
            $table->string('father_title_suffix', 150)->nullable()->change();
            $table->string('mother_title_prefix', 150)->nullable()->change();
            $table->string('mother_title_suffix', 150)->nullable()->change();
            $table->string('father_education', 100)->nullable()->change();
            $table->string('mother_education', 100)->nullable()->change();
            $table->string('guardian_education', 100)->nullable()->change();
            $table->string('guardian_relation', 100)->nullable()->change();
            $table->string('emergency_contact_name', 150)->nullable()->change();
            $table->string('emergency_contact_relation', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('father_title_prefix', 30)->nullable()->change();
            $table->string('father_title_suffix', 30)->nullable()->change();
            $table->string('mother_title_prefix', 30)->nullable()->change();
            $table->string('mother_title_suffix', 30)->nullable()->change();
        });
    }
};
