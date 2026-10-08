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
        'fee_categories',
        'payment_summary',
        'referral',
        'has_payment_access',
        'category',
        'jenjang_code',
        'jenjang_name',
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
     * Kategori Murid (Reguler / MBK / etc)
     */
    public function getCategoryAttribute(): string
    {
        return $this->class_program ?: 'Reguler';
    }

    /**
     * Kode Jenjang Singkat Utama (KB, TK, TPA, TPQ)
     */
    public function getJenjangCodeAttribute(): string
    {
        if (!empty($this->raw_payload['jenjang_code'])) {
            return strtoupper($this->raw_payload['jenjang_code']);
        }
        if (!empty($this->raw_payload['jenjang']['code'])) {
            return strtoupper($this->raw_payload['jenjang']['code']);
        }

        $lvl = strtoupper(trim($this->admission_level ?? ''));
        if (str_starts_with($lvl, 'KB')) return 'KB';
        if (str_starts_with($lvl, 'TK')) return 'TK';
        if (str_starts_with($lvl, 'TPA') || str_starts_with($lvl, 'DAYCARE')) return 'TPA';
        if (str_starts_with($lvl, 'TPQ')) return 'TPQ';
        return 'TK';
    }

    /**
     * Nama Lengkap Jenjang Utama (Playgroup (KB), Taman Kanak-kanak (TK), Daycare (TPA), TPQ)
     */
    public function getJenjangNameAttribute(): string
    {
        if (!empty($this->raw_payload['jenjang']['name'])) {
            return $this->raw_payload['jenjang']['name'];
        }
        $code = $this->jenjang_code;
        return match($code) {
            'KB' => 'Playgroup (KB)',
            'TK' => 'Taman Kanak-kanak (TK)',
            'TPA' => 'Daycare (TPA)',
            'TPQ' => 'TPQ',
            default => $code ?: 'PAUD',
        };
    }

    /**
     * Seluruh Kode Jenjang yang didaftarkan murid (Kelas Utama + Layanan Tambahan)
     */
    public function getAllJenjangCodesAttribute(): array
    {
        $codes = [];
        $primary = $this->jenjang_code;
        if ($primary) {
            $codes[] = $primary;
        }

        if (!empty($this->raw_payload['all_jenjang_codes']) && is_array($this->raw_payload['all_jenjang_codes'])) {
            $codes = array_merge($codes, $this->raw_payload['all_jenjang_codes']);
        }

        foreach ($this->services_list as $srv) {
            $srvUpper = strtoupper($srv);
            if (str_contains($srvUpper, 'TPA') || str_contains($srvUpper, 'PENITIPAN') || str_contains($srvUpper, 'DAYCARE')) {
                $codes[] = 'TPA';
            }
            if (str_contains($srvUpper, 'TPQ') || str_contains($srvUpper, 'QURAN') || str_contains($srvUpper, 'MENGAJI')) {
                $codes[] = 'TPQ';
            }
            if (str_contains($srvUpper, 'KB') || str_contains($srvUpper, 'PLAYGROUP')) {
                $codes[] = 'KB';
            }
            if (str_contains($srvUpper, 'TK') || str_contains($srvUpper, 'KANAK')) {
                $codes[] = 'TK';
            }
        }

        return array_values(array_unique(array_filter($codes)));
    }

    /**
     * Layanan Tambahan (Daycare, TPQ, Fullday, dll)
     */
    public function getServicesListAttribute(): array
    {
        if (is_array($this->extra_services) && !empty($this->extra_services)) {
            return array_map(function($s) {
                return is_array($s) ? ($s['name'] ?? 'Layanan') : (string)$s;
            }, $this->extra_services);
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
     * Rincian Kategori Biaya Lengkap dari SPMB
     */
    public function getFeeCategoriesAttribute(): array
    {
        return $this->raw_payload['fee_categories'] ?? [];
    }

    /**
     * Ringkasan Tagihan Pembayaran dari SPMB
     */
    public function getPaymentSummaryAttribute(): ?array
    {
        return $this->raw_payload['payment_summary'] ?? null;
    }

    /**
     * Saluran Informasi & Referral dari SPMB
     */
    public function getReferralAttribute(): ?array
    {
        return $this->raw_payload['referral'] ?? null;
    }

    /**
     * Cek apakah data keuangan / pembayaran diizinkan dan tersedia
     */
    public function getHasPaymentAccessAttribute(): bool
    {
        return !empty($this->payment_status)
            || (!empty($this->payments) && is_array($this->payments) && count($this->payments) > 0)
            || (!empty($this->raw_payload['fee_categories']) && is_array($this->raw_payload['fee_categories']) && count($this->raw_payload['fee_categories']) > 0);
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
            ?? ($payload['type']['name'] ?? ($payload['type'] ?? 'Murid Baru'))));
        if (is_array($regType)) {
            $regType = $regType['name'] ?? 'Murid Baru';
        }

        $admLevel = $payload['admission_level'] 
            ?? ($payload['target_class'] 
            ?? ($payload['grade']['name'] ?? ($payload['grade'] ?? ($payload['class_level'] ?? null))));
        if (is_array($admLevel)) {
            $admLevel = $admLevel['name'] ?? null;
        }

        $wave = $payload['wave'] ?? null;
        if (is_array($wave)) {
            $wave = $wave['name'] ?? null;
        }

        $classProgram = $payload['class_program'] ?? ($payload['category'] ?? 'Reguler');
        if (is_array($classProgram)) {
            $classProgram = $classProgram['name'] ?? 'Reguler';
        }

        $unitCode = $payload['unit']['code'] ?? ($payload['unit_code'] ?? 'PAUD');
        $unitName = $payload['unit']['name'] ?? ($payload['unit_name'] ?? 'PAUD Terpadu Anak Saleh');

        $services = $payload['extra_services'] 
            ?? ($payload['services'] 
            ?? ($payload['additional_services'] ?? []));

        return self::updateOrCreate(
            ['registration_number' => $regNumber],
            [
                'spmb_registration_id' => $payload['id'] ?? null,
                'academic_year' => $payload['period'] ?? null,
                'unit_code' => $unitCode,
                'unit_name' => $unitName,
                'wave' => $wave,
                'class_program' => $classProgram,
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
                'payment_status' => $payload['payment_status'] ?? null,
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
