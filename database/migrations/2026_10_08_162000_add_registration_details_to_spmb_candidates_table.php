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
        Schema::table('spmb_candidates', function (Blueprint $table) {
            if (!Schema::hasColumn('spmb_candidates', 'registration_type')) {
                $table->string('registration_type')->nullable()->after('class_program'); // Murid Baru, Mutasi Masuk / Pindahan
            }
            if (!Schema::hasColumn('spmb_candidates', 'admission_level')) {
                $table->string('admission_level')->nullable()->after('registration_type'); // TK A, TK B, KB, Daycare
            }
            if (!Schema::hasColumn('spmb_candidates', 'extra_services')) {
                $table->json('extra_services')->nullable()->after('admission_level'); // ['Daycare', 'TPQ', etc]
            }
        });

        // Backfill existing records from raw_payload or class_program
        $candidates = DB::table('spmb_candidates')->get();
        foreach ($candidates as $c) {
            $raw = !empty($c->raw_payload) ? (is_array($c->raw_payload) ? $c->raw_payload : json_decode($c->raw_payload, true)) : [];
            
            $regType = $raw['registration_type'] 
                ?? ($raw['entry_type'] 
                ?? ($raw['admission_type'] 
                ?? ($raw['type'] ?? 'Murid Baru')));

            $admLevel = $raw['admission_level'] 
                ?? ($raw['target_class'] 
                ?? ($raw['grade'] 
                ?? ($raw['class_level'] ?? null)));

            if (!$admLevel) {
                // If admission_level is not explicitly in payload, derive from class_program or previous_school
                $prog = strtolower($c->class_program ?? '');
                if (str_contains($prog, 'tk-a') || str_contains($prog, 'tk a')) {
                    $admLevel = 'TK A';
                } elseif (str_contains($prog, 'tk-b') || str_contains($prog, 'tk b')) {
                    $admLevel = 'TK B';
                } elseif (str_contains($prog, 'kb') || str_contains($prog, 'bermain')) {
                    $admLevel = 'Kelompok Bermain (KB)';
                } elseif (str_contains($prog, 'daycare') || str_contains($prog, 'tpa')) {
                    $admLevel = 'Daycare / TPA';
                } else {
                    $admLevel = 'TK A';
                }
            }

            $services = $raw['extra_services'] 
                ?? ($raw['services'] 
                ?? ($raw['additional_services'] ?? []));

            DB::table('spmb_candidates')
                ->where('id', $c->id)
                ->update([
                    'registration_type' => $regType ?: 'Murid Baru',
                    'admission_level' => $admLevel,
                    'extra_services' => is_array($services) ? json_encode($services) : (is_string($services) ? $services : json_encode([])),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spmb_candidates', function (Blueprint $table) {
            if (Schema::hasColumn('spmb_candidates', 'registration_type')) {
                $table->dropColumn('registration_type');
            }
            if (Schema::hasColumn('spmb_candidates', 'admission_level')) {
                $table->dropColumn('admission_level');
            }
            if (Schema::hasColumn('spmb_candidates', 'extra_services')) {
                $table->dropColumn('extra_services');
            }
        });
    }
};
