<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'nis',
        'nisn',
        'nik',
        'no_kk',
        'no_akta',
        'spmb_candidate_id',
        'jenjang_id',
        'classroom_id',
        'daycare_classroom_id',
        'is_tpq',
        'tpq_classroom_id',
        'class_level_id',
        'sub_unit',
        'academic_year_id',
        'full_name',
        'nickname',
        'gender',
        'birth_place',
        'birth_date',
        'religion',
        'citizenship',
        'address',
        'rt',
        'rw',
        'kelurahan',
        'kecamatan',
        'city',
        'province',
        'postal_code',
        'living_with',
        'transportation',
        'child_order',
        'siblings_count',
        'blood_type',
        'special_needs',
        'height',
        'weight',
        'head_circumference',
        'arm_circumference',
        'distance_to_school',
        'travel_time_minutes',
        'previous_school',
        'student_photo_url',
        'father_name',
        'father_phone',
        'father_job',
        'mother_name',
        'mother_phone',
        'mother_job',
        'guardian_name',
        'guardian_phone',
        'parent_phone',
        'parent_email',
        'family_data',
        'health_habits',
        'development_psychology',
        'pin_access',
        'documents',
        'status',
        'enrolled_date',
        'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'enrolled_date' => 'date',
        'is_tpq' => 'boolean',
        'documents' => 'array',
        'family_data' => 'array',
        'health_habits' => 'array',
        'development_psychology' => 'array',
        'height' => 'float',
        'weight' => 'float',
        'head_circumference' => 'float',
        'arm_circumference' => 'float',
        'child_order' => 'integer',
        'siblings_count' => 'integer',
        'travel_time_minutes' => 'integer',
    ];

    protected $appends = [
        'formatted_gender',
        'age',
        'avatar_initials',
        'clean_parent_phone',
        'whatsapp_url',
    ];

    public function jenjang(): BelongsTo
    {
        return $this->belongsTo(Jenjang::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function daycareClassroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'daycare_classroom_id');
    }

    public function tpqClassroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'tpq_classroom_id');
    }

    public function classLevel(): BelongsTo
    {
        return $this->belongsTo(ClassLevel::class);
    }

    public function classroomHistories()
    {
        return $this->hasMany(StudentClassroomHistory::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function spmbCandidate(): BelongsTo
    {
        return $this->belongsTo(SpmbCandidate::class);
    }

    public function attendances()
    {
        return $this->hasMany(StudentAttendance::class);
    }

    public function ddtkRecords()
    {
        return $this->hasMany(StudentDdtkRecord::class);
    }

    public function uploadedDocuments()
    {
        return $this->hasMany(StudentDocument::class);
    }

    /**
     * Unified document completeness checklist aggregated from StudentDocuments, Student.documents, and SPMB.
     */
    public function getDocumentChecklistAttribute(): array
    {
        $akta = null;
        $kk = null;
        $foto = $this->student_photo_url ?: null;
        $ktp = null;
        $kia = null;
        $otherList = [];

        // 1. Check from uploadedDocuments relation
        $docs = $this->relationLoaded('uploadedDocuments') ? $this->uploadedDocuments : $this->uploadedDocuments()->get();

        foreach ($docs as $d) {
            $type = strtolower($d->document_type);
            $url = $d->file_url;
            if (str_contains($type, 'akta')) {
                $akta = ['url' => $url, 'title' => $d->title, 'source' => 'Arsip Murid', 'id' => $d->id];
            } elseif (str_contains($type, 'kk') || str_contains($type, 'kartu_keluarga')) {
                $kk = ['url' => $url, 'title' => $d->title, 'source' => 'Arsip Murid', 'id' => $d->id];
            } elseif (str_contains($type, 'foto')) {
                $foto = $url;
            } elseif (str_contains($type, 'ktp')) {
                $ktp = ['url' => $url, 'title' => $d->title, 'source' => 'Arsip Murid', 'id' => $d->id];
            } elseif (str_contains($type, 'kia') || str_contains($type, 'imunisasi')) {
                $kia = ['url' => $url, 'title' => $d->title, 'source' => 'Arsip Murid', 'id' => $d->id];
            } else {
                $otherList[] = ['url' => $url, 'title' => $d->title, 'source' => 'Arsip Murid', 'type' => $d->type_label, 'id' => $d->id];
            }
        }

        // 2. Check from students.documents JSON array
        if (is_array($this->documents)) {
            foreach ($this->documents as $k => $v) {
                $url = is_array($v) ? ($v['url'] ?? null) : $v;
                if (!$url || $url === '#') continue;
                $key = strtolower(is_string($k) ? $k : ($v['key'] ?? ''));
                if (!$akta && (str_contains($key, 'akta') || str_contains($key, 'birth_certificate'))) {
                    $akta = ['url' => $url, 'title' => 'Akta Kelahiran', 'source' => 'Data Murid'];
                } elseif (!$kk && (str_contains($key, 'kk') || str_contains($key, 'family_card'))) {
                    $kk = ['url' => $url, 'title' => 'Kartu Keluarga', 'source' => 'Data Murid'];
                } elseif (!$foto && (str_contains($key, 'foto') || str_contains($key, 'photo'))) {
                    $foto = $url;
                } elseif (!$ktp && str_contains($key, 'ktp')) {
                    $ktp = ['url' => $url, 'title' => 'KTP Orang Tua', 'source' => 'Data Murid'];
                } elseif (!$kia && (str_contains($key, 'kia') || str_contains($key, 'imunisasi'))) {
                    $kia = ['url' => $url, 'title' => 'Buku KIA / Imunisasi', 'source' => 'Data Murid'];
                }
            }
        }

        // 3. Check from spmbCandidate if linked
        if ($this->spmbCandidate && is_array($this->spmbCandidate->formatted_documents)) {
            foreach ($this->spmbCandidate->formatted_documents as $sDoc) {
                $url = $sDoc['url'] ?? null;
                if (!$url || $url === '#') continue;
                $key = strtolower($sDoc['key'] ?? '');
                $name = strtolower($sDoc['name'] ?? '');

                if (!$akta && (str_contains($key, 'birth_certificate') || str_contains($name, 'akta'))) {
                    $akta = ['url' => $url, 'title' => 'Akta Kelahiran', 'source' => 'SPMB'];
                } elseif (!$kk && (str_contains($key, 'family_card') || str_contains($name, 'kartu keluarga') || str_contains($name, 'kk'))) {
                    $kk = ['url' => $url, 'title' => 'Kartu Keluarga', 'source' => 'SPMB'];
                } elseif (!$foto && (str_contains($key, 'photo') || str_contains($name, 'foto'))) {
                    $foto = $url;
                } elseif (!$kia && (str_contains($key, 'kia') || str_contains($key, 'student_card'))) {
                    $kia = ['url' => $url, 'title' => 'KIA / Kartu Pelajar', 'source' => 'SPMB'];
                }
            }
            if (!$foto && $this->spmbCandidate->student_photo_url) {
                $foto = $this->spmbCandidate->student_photo_url;
            }
        }

        $coreCount = ($akta ? 1 : 0) + ($kk ? 1 : 0) + ($foto ? 1 : 0);
        $totalCount = $coreCount + ($ktp ? 1 : 0) + ($kia ? 1 : 0) + count($otherList);

        return [
            'akta' => $akta,
            'kk' => $kk,
            'foto' => $foto,
            'ktp' => $ktp,
            'kia' => $kia,
            'other_docs' => $otherList,
            'is_complete' => ($akta !== null && $kk !== null && $foto !== null),
            'core_count' => $coreCount,
            'total_count' => $totalCount,
        ];
    }

    /**
     * Human-readable gender.
     */
    public function getFormattedGenderAttribute(): string
    {
        $g = strtoupper((string)$this->gender);
        if ($g === 'L' || $g === 'LAKI-LAKI' || $g === 'MALE') return 'Laki-laki';
        if ($g === 'P' || $g === 'PEREMPUAN' || $g === 'FEMALE') return 'Perempuan';
        return $this->gender ?: '-';
    }

    /**
     * Calculated age string.
     */
    public function getAgeAttribute(): ?string
    {
        if (!$this->birth_date) return null;
        $diff = \Carbon\Carbon::parse($this->birth_date)->diff(\Carbon\Carbon::now());
        return "{$diff->y} th " . ($diff->m > 0 ? "{$diff->m} bln" : "");
    }

    /**
     * Avatar initials.
     */
    public function getAvatarInitialsAttribute(): string
    {
        $names = explode(' ', trim($this->full_name));
        $initials = '';
        foreach (array_slice($names, 0, 2) as $n) {
            $initials .= mb_substr($n, 0, 1);
        }
        return strtoupper($initials ?: 'S');
    }

    /**
     * Clean phone number for WhatsApp.
     */
    public function getCleanParentPhoneAttribute(): ?string
    {
        $raw = $this->parent_phone ?: $this->father_phone ?: $this->mother_phone ?: $this->guardian_phone;
        if (!$raw) return null;

        $clean = preg_replace('/[^0-9]/', '', (string)$raw);
        if (str_starts_with($clean, '08')) {
            $clean = '628' . substr($clean, 2);
        } elseif (str_starts_with($clean, '8')) {
            $clean = '628' . substr($clean, 1);
        }
        return $clean;
    }

    /**
     * Direct WhatsApp URL.
     */
    public function getWhatsappUrlAttribute(): ?string
    {
        $phone = $this->clean_parent_phone;
        if (!$phone || strlen($phone) < 8) return null;
        $text = urlencode("Halo Ayah/Bunda {$this->full_name}, kami dari SANS PAUD Malang.");
        return "https://wa.me/{$phone}?text={$text}";
    }
}
