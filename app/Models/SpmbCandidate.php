<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpmbCandidate extends Model
{
    use HasFactory;

    protected $table = 'spmb_candidates';

    protected $fillable = [
        'spmb_registration_id',
        'registration_number',
        'academic_year',
        'unit_code',
        'unit_name',
        'wave',
        'class_program',
        'registration_type',
        'admission_level',
        'extra_services',
        'full_name',
        'nickname',
        'nik',
        'nisn',
        'gender',
        'birth_place',
        'birth_date',
        'religion',
        'address',
        'city',
        'province',
        'previous_school',
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
        'registration_status',
        'payment_status',
        'verified_at',
        'student_photo_url',
        'documents',
        'payments',
        'raw_payload',
        'student_id',
        'is_enrolled',
        'enrolled_at',
        'synced_at',
    ];

    protected $casts = [
        'birth_date' => 'date:Y-m-d',
        'verified_at' => 'datetime',
        'enrolled_at' => 'datetime',
        'synced_at' => 'datetime',
        'documents' => 'array',
        'payments' => 'array',
        'extra_services' => 'array',
        'raw_payload' => 'array',
        'is_enrolled' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    protected $appends = [
        'whatsapp_url', 
        'formatted_birth_date',
        'student_photo_url',
        'formatted_documents',
        'age_string',
        'formatted_address',
        'father_nik',
        'mother_nik',
        'father_education',
        'mother_education',
        'father_income',
        'mother_income',
        'formatted_payments',
        'category',
        'services_list',
    ];

    /**
     * Mutator & Accessor untuk normalisasi format Tahun Ajaran (misal 2026-2027 -> 2026/2027)
     */
    public function getAcademicYearAttribute($value): ?string
    {
        return $value ? str_replace('-', '/', $value) : null;
    }

    public function setAcademicYearAttribute($value): void
    {
        $this->attributes['academic_year'] = $value ? str_replace('-', '/', trim($value)) : null;
    }

    /**
     * URL Foto Calon Murid
     */
    public function getStudentPhotoUrlAttribute(): ?string
    {
        if (!empty($this->attributes['student_photo_url'])) {
            return $this->attributes['student_photo_url'];
        }

        if (is_array($this->documents)) {
            foreach ($this->documents as $doc) {
                if (is_array($doc)) {
                    $key = $doc['key'] ?? '';
                    $name = strtolower($doc['name'] ?? '');
                    if (in_array($key, ['student_photo_path', 'student_photo', 'photo']) || str_contains($name, 'pas foto') || str_contains($name, 'foto')) {
                        if (!empty($doc['url'])) return $doc['url'];
                    }
                }
            }
            return $this->documents['student_photo'] 
                ?? $this->documents['student_photo_path'] 
                ?? $this->documents['photo']
                ?? null;
        }

        return $this->raw_payload['student_bio']['photo_url'] ?? null;
    }

    /**
     * Format Dokumen Dinamis & Terstruktur
     */
    public function getFormattedDocumentsAttribute(): array
    {
        if (!is_array($this->documents) || empty($this->documents)) {
            return [];
        }

        $list = [];
        $labelMap = [
            'student_photo' => 'Pas Foto Calon Murid (Foto Formal)',
            'student_photo_path' => 'Pas Foto Calon Murid (Foto Formal)',
            'birth_certificate' => 'Akta Kelahiran',
            'birth_certificate_path' => 'Akta Kelahiran',
            'family_card' => 'Kartu Keluarga (KK)',
            'family_card_path' => 'Kartu Keluarga (KK)',
            'diploma_certificate' => 'Ijazah / Surat Keterangan Aktif Sekolah',
            'diploma_certificate_path' => 'Ijazah / Surat Keterangan Aktif Sekolah',
            'student_card' => 'NISN / KIA / Kartu Pelajar (Opsional)',
            'student_card_path' => 'NISN / KIA / Kartu Pelajar (Opsional)',
            'special_needs_assessment_path' => 'Asesmen Kebutuhan Khusus (Jika Ada)',
            'payment_receipt_path' => 'Bukti Pembayaran Pendaftaran',
        ];

        foreach ($this->documents as $k => $v) {
            if (is_array($v)) {
                $list[] = [
                    'key' => $v['key'] ?? (is_string($k) ? $k : 'doc_' . count($list)),
                    'name' => $v['name'] ?? ($v['label'] ?? ($labelMap[$v['key'] ?? ''] ?? 'Berkas Dokumen')),
                    'url' => $v['url'] ?? '#',
                ];
            } elseif (is_string($v) && !empty($v)) {
                $label = $labelMap[$k] ?? ucwords(str_replace(['_', '-'], ' ', $k));
                $list[] = [
                    'key' => $k,
                    'name' => $label,
                    'url' => $v,
                ];
            }
        }

        return $list;
    }

    /**
     * Format Tanggal Lahir Bahasa Indonesia
     */
    public function getFormattedBirthDateAttribute(): ?string
    {
        if (!$this->birth_date) return null;
        return $this->birth_date->translatedFormat('d F Y');
    }

    /**
     * Usia Calon Murid
     */
    public function getAgeStringAttribute(): ?string
    {
        if (!$this->birth_date) return null;
        $diff = \Carbon\Carbon::parse($this->birth_date)->diff(\Carbon\Carbon::now());
        return "{$diff->y} th " . ($diff->m > 0 ? "{$diff->m} bln" : "");
    }

    /**
     * Alamat Lengkap Terstruktur
     */
    public function getFormattedAddressAttribute(): string
    {
        $bioAddr = $this->raw_payload['student_bio']['address'] ?? [];
        if (is_array($bioAddr) && !empty($bioAddr)) {
            $parts = [];
            if (!empty($bioAddr['street'])) $parts[] = $bioAddr['street'];
            $rtRw = '';
            if (!empty($bioAddr['rt'])) $rtRw .= 'RT ' . $bioAddr['rt'];
            if (!empty($bioAddr['rw'])) $rtRw .= ($rtRw ? ' / ' : '') . 'RW ' . $bioAddr['rw'];
            if ($rtRw) $parts[] = $rtRw;
            if (!empty($bioAddr['village'])) $parts[] = 'Kel. ' . $bioAddr['village'];
            if (!empty($bioAddr['district'])) $parts[] = 'Kec. ' . $bioAddr['district'];
            if (!empty($bioAddr['city'])) $parts[] = $bioAddr['city'];
            if (!empty($bioAddr['province'])) $parts[] = $bioAddr['province'];
            if (!empty($bioAddr['postal_code'])) $parts[] = 'Kode Pos: ' . $bioAddr['postal_code'];
            if (!empty($parts)) {
                return implode(', ', $parts);
            }
            if (!empty($bioAddr['full_address'])) {
                return $bioAddr['full_address'];
            }
        }

        return $this->address ?: '-';
    }

    public function getFatherNikAttribute(): ?string
    {
        return $this->raw_payload['parent_info']['father']['nik'] ?? null;
    }

    public function getMotherNikAttribute(): ?string
    {
        return $this->raw_payload['parent_info']['mother']['nik'] ?? null;
    }

    public function getFatherEducationAttribute(): ?string
    {
        return $this->raw_payload['parent_info']['father']['education'] ?? null;
    }

    public function getMotherEducationAttribute(): ?string
    {
        return $this->raw_payload['parent_info']['mother']['education'] ?? null;
    }

    public function getFatherIncomeAttribute(): ?string
    {
        return $this->raw_payload['parent_info']['father']['income'] ?? null;
    }

    public function getMotherIncomeAttribute(): ?string
    {
        return $this->raw_payload['parent_info']['mother']['income'] ?? null;
    }

    /**
     * Kategori Murid (Reguler / MBK)
     */
    public function getCategoryAttribute(): string
    {
        $prog = strtolower($this->class_program ?? ($this->raw_payload['class_program'] ?? ''));
        if (str_contains($prog, 'mbk') || str_contains($prog, 'kebutuhan khusus')) {
            return 'MBK';
        }
        return 'Reguler';
    }

    /**
     * Layanan Tambahan (Daycare, TPQ, Fullday, dll)
     */
    public function getServicesListAttribute(): array
    {
        if (is_array($this->extra_services) && !empty($this->extra_services)) {
            return $this->extra_services;
        }

        $rawServices = $this->raw_payload['extra_services'] 
            ?? ($this->raw_payload['services'] 
            ?? ($this->raw_payload['additional_services'] ?? []));

        if (is_array($rawServices) && !empty($rawServices)) {
            return array_map(function($s) {
                return is_array($s) ? ($s['name'] ?? 'Layanan') : (string)$s;
            }, $rawServices);
        }

        return [];
    }

    /**
     * Riwayat Pembayaran Terstruktur
     */
    public function getFormattedPaymentsAttribute(): array
    {
        $rawPayments = $this->payments ?? ($this->raw_payload['payments'] ?? []);
        if (!is_array($rawPayments) || empty($rawPayments)) {
            return [];
        }

        $list = [];
        foreach ($rawPayments as $p) {
            if (!is_array($p)) continue;
            $amount = (float)($p['amount'] ?? 0);
            $paidAt = !empty($p['paid_at']) ? \Carbon\Carbon::parse($p['paid_at'])->translatedFormat('d M Y, H:i') : null;
            $status = strtolower($p['status'] ?? 'pending');
            $isPaid = in_array($status, ['paid', 'lunas', 'settlement', 'success']);

            $list[] = [
                'invoice_number' => $p['invoice_number'] ?? '-',
                'payment_type' => $p['payment_type'] ?? 'Pendaftaran SPMB',
                'amount' => $amount,
                'formatted_amount' => 'Rp ' . number_format($amount, 0, ',', '.'),
                'payment_method' => $p['payment_method'] ?? ($p['payment_channel'] ?? 'Online Payment'),
                'status' => $status,
                'is_paid' => $isPaid,
                'paid_at' => $paidAt,
            ];
        }

        return $list;
    }

    /**
     * Upsert a candidate record from incoming SPMB JSON payload.
     *
     * @param array $payload
     * @return self
     */
    public static function syncFromPayload(array $payload): self
    {
        $regNumber = $payload['registration_number'] 
            ?? ($payload['registration_no'] 
            ?? ($payload['id_label'] 
            ?? (!empty($payload['id']) ? ('SPMB-' . str_pad($payload['id'], 5, '0', STR_PAD_LEFT)) : null)));

        if (!$regNumber) {
            throw new \InvalidArgumentException('Registration number is missing in SPMB payload.');
        }

        $bio = $payload['student_bio'] ?? [];
        $address = $bio['address'] ?? [];
        $parents = $payload['parent_info'] ?? [];
        $father = $parents['father'] ?? [];
        $mother = $parents['mother'] ?? [];
        $guardian = $parents['guardian'] ?? [];
        $contact = $parents['primary_contact'] ?? [];
        $school = $payload['school_origin'] ?? [];
        $documents = $payload['documents'] ?? [];
        $payments = $payload['payments'] ?? [];

        $parentPhone = $contact['whatsapp'] ?? ($father['phone'] ?? ($mother['phone'] ?? null));

        // Format birth date
        $birthDate = null;
        if (!empty($bio['birth_date'])) {
            try {
                $birthDate = \Carbon\Carbon::parse($bio['birth_date'])->format('Y-m-d');
            } catch (\Exception $e) {
                $birthDate = null;
            }
        }

        $verifiedAt = null;
        if (!empty($payload['verified_at'])) {
            try {
                $verifiedAt = \Carbon\Carbon::parse($payload['verified_at']);
            } catch (\Exception $e) {
                $verifiedAt = null;
            }
        }

        $studentPhotoUrl = null;
        if (!empty($bio['photo_url'])) {
            $studentPhotoUrl = $bio['photo_url'];
        } elseif (is_array($documents)) {
            foreach ($documents as $doc) {
                if (is_array($doc)) {
                    $k = $doc['key'] ?? '';
                    $n = strtolower($doc['name'] ?? '');
                    if (in_array($k, ['student_photo_path', 'student_photo', 'photo']) || str_contains($n, 'pas foto') || str_contains($n, 'foto')) {
                        if (!empty($doc['url'])) {
                            $studentPhotoUrl = $doc['url'];
                            break;
                        }
                    }
                }
            }
            if (!$studentPhotoUrl) {
                $studentPhotoUrl = $documents['student_photo'] ?? ($documents['student_photo_path'] ?? null);
            }
        }

        $regType = $payload['registration_type'] 
            ?? ($payload['entry_type'] 
            ?? ($payload['admission_type'] 
            ?? ($payload['type'] ?? 'Murid Baru')));

        $admLevel = $payload['admission_level'] 
            ?? ($payload['target_class'] 
            ?? ($payload['grade'] 
            ?? ($payload['class_level'] ?? null)));

        if (!$admLevel) {
            $prog = strtolower($payload['class_program'] ?? '');
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

        $services = $payload['extra_services'] 
            ?? ($payload['services'] 
            ?? ($payload['additional_services'] ?? []));

        return self::updateOrCreate(
            ['registration_number' => $regNumber],
            [
                'spmb_registration_id' => $payload['id'] ?? null,
                'academic_year' => $payload['period'] ?? null,
                'unit_code' => $payload['unit']['code'] ?? 'PAUD',
                'unit_name' => $payload['unit']['name'] ?? 'PAUD Terpadu Anak Saleh',
                'wave' => $payload['wave'] ?? null,
                'class_program' => $payload['class_program'] ?? 'Reguler',
                'registration_type' => $regType ?: 'Murid Baru',
                'admission_level' => $admLevel,
                'extra_services' => $services,
                'full_name' => $bio['full_name'] ?? ($payload['full_name'] ?? 'Pendaftar SPMB'),
                'nickname' => $bio['nickname'] ?? null,
                'nik' => $bio['nik'] ?? null,
                'nisn' => $bio['nisn'] ?? null,
                'gender' => $bio['gender'] ?? null,
                'birth_place' => $bio['birth_place'] ?? null,
                'birth_date' => $birthDate,
                'religion' => $bio['religion'] ?? 'Islam',
                'address' => $address['full_address'] ?? ($address['street'] ?? null),
                'city' => $address['city'] ?? null,
                'province' => $address['province'] ?? null,
                'previous_school' => $school['previous_school'] ?? null,
                'father_name' => $father['name'] ?? null,
                'father_phone' => $father['phone'] ?? null,
                'father_job' => $father['job'] ?? null,
                'mother_name' => $mother['name'] ?? null,
                'mother_phone' => $mother['phone'] ?? null,
                'mother_job' => $mother['job'] ?? null,
                'guardian_name' => $guardian['name'] ?? null,
                'guardian_phone' => $guardian['phone'] ?? null,
                'parent_phone' => $parentPhone,
                'parent_email' => $contact['email'] ?? null,
                'registration_status' => $payload['registration_status'] ?? 'verified',
                'payment_status' => $payload['payment_status'] ?? 'unpaid',
                'verified_at' => $verifiedAt,
                'student_photo_url' => $studentPhotoUrl,
                'documents' => $documents,
                'payments' => $payments,
                'raw_payload' => $payload,
                'synced_at' => now(),
            ]
        );
    }

    /**
     * Get clean WhatsApp number.
     */
    public function getCleanPhone(): ?string
    {
        $phone = $this->parent_phone ?? $this->father_phone ?? $this->mother_phone;
        if (!$phone) return null;

        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($cleaned, '0')) {
            $cleaned = '62' . substr($cleaned, 1);
        } elseif (!str_starts_with($cleaned, '62')) {
            $cleaned = '62' . $cleaned;
        }

        return $cleaned;
    }

    /**
     * Direct WhatsApp URL.
     */
    public function getWhatsAppUrlAttribute(): ?string
    {
        $cleanPhone = $this->getCleanPhone();
        if (!$cleanPhone) return null;

        $msg = urlencode("Assalamu'alaikum Wr. Wb. Bapak/Ibu wali dari ananda *{$this->full_name}* (No. Registrasi: {$this->registration_number}). Terima kasih atas pendaftarannya di SANS PAUD Malang.");
        return "https://wa.me/{$cleanPhone}?text={$msg}";
    }
}
