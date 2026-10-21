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
            $table->string('distance_to_school', 150)->nullable()->change();
            $table->string('citizenship', 50)->nullable()->change();
            $table->string('living_with', 100)->nullable()->change();
            $table->string('transportation', 100)->nullable()->change();
            $table->string('postal_code', 20)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('distance_to_school', 50)->nullable()->change();
            $table->string('citizenship', 20)->nullable()->change();
            $table->string('living_with', 50)->nullable()->change();
            $table->string('transportation', 50)->nullable()->change();
            $table->string('postal_code', 10)->nullable()->change();
        });
    }
};
