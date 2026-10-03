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
        if (!Schema::hasTable('jenjangs')) {
            Schema::create('jenjangs', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->nullable();
                $table->integer('order')->default(1);
                $table->boolean('is_active')->default(true)->index();
                $table->text('description')->nullable();
                $table->timestamps();
            });

            // Seed default 4 Jenjang
            $now = now();
            DB::table('jenjangs')->insert([
                [
                    'name' => 'Playgroup (KB)',
                    'code' => 'KB',
                    'order' => 1,
                    'is_active' => true,
                    'description' => 'Kelompok Bermain (usia 2 - 4 tahun)',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Taman Kanak-kanak (TK)',
                    'code' => 'TK',
                    'order' => 2,
                    'is_active' => true,
                    'description' => 'Taman Kanak-kanak (usia 4 - 6 tahun)',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Daycare (TPA)',
                    'code' => 'DAYCARE',
                    'order' => 3,
                    'is_active' => true,
                    'description' => 'Layanan Taman Pengasuhan Anak / Daycare',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'TPQ',
                    'code' => 'TPQ',
                    'order' => 4,
                    'is_active' => true,
                    'description' => 'Taman Pendidikan Al-Qur\'an',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jenjangs');
    }
};
