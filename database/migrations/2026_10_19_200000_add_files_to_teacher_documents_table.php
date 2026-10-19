<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_documents', function (Blueprint $table) {
            $table->json('files')->nullable()->after('title');
            $table->string('file_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('teacher_documents', function (Blueprint $table) {
            $table->dropColumn('files');
            $table->string('file_path')->nullable(false)->change();
        });
    }
};
