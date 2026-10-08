<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\SpmbCandidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SpmbIntegrationService
{
    /**
     * Get configured SPMB Base URL.
     */
    public static function getBaseUrl(): string
    {
        $url = Setting::get('spmb_api_url', 'http://sans-spmb.test');
        return rtrim($url, '/');
    }

    /**
     * Get configured API Token.
     */
    public static function getApiToken(): ?string
    {
        return Setting::get('spmb_api_token');
    }

    /**
     * Get configured Webhook Secret.
     */
    public static function getWebhookSecret(): ?string
    {
        return Setting::get('spmb_webhook_secret');
    }

    /**
     * Test connection to SPMB API.
     *
     * @return array
     */
    public function testConnection(): array
    {
        $baseUrl = self::getBaseUrl();
        $token = self::getApiToken();

        if (empty($baseUrl)) {
            return [
                'success' => false,
                'message' => 'URL Aplikasi SPMB belum dikonfigurasi di Pengaturan.',
            ];
        }

        if (empty($token)) {
            return [
                'success' => false,
                'message' => 'Token API SPMB belum diisi di Pengaturan.',
            ];
        }

        try {
            $endpoint = "{$baseUrl}/api/v1/candidates";
            $response = Http::withToken($token)
                ->timeout(8)
                ->acceptJson()
                ->get($endpoint, ['per_page' => 1]);

            if ($response->successful()) {
                $body = $response->json();
                $total = $body['meta']['total'] ?? count($body['data'] ?? []);
                return [
                    'success' => true,
                    'status_code' => $response->status(),
                    'message' => "Koneksi berhasil! Terhubung ke SPMB ({$total} calon murid terdeteksi).",
                    'data' => $body,
                ];
            }

            if ($response->status() === 401) {
                return [
                    'success' => false,
                    'status_code' => 401,
                    'message' => 'Autentikasi Gagal: Token API SPMB tidak valid atau telah dicabut.',
                ];
            }

            if ($response->status() === 403) {
                return [
                    'success' => false,
                    'status_code' => 403,
                    'message' => 'Akses Ditolak: Client API tidak memiliki hak akses untuk Unit PAUD.',
                ];
            }

            return [
                'success' => false,
                'status_code' => $response->status(),
                'message' => "Server SPMB mengembalikan status {$response->status()}: " . ($response->json('message') ?? 'Unknown error'),
            ];
        } catch (\Exception $e) {
            Log::error('SPMB test connection error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal terhubung ke host SPMB: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Pull / Sync candidate registrations from SPMB API.
     *
     * @param string|null $period
     * @param string|null $status
     * @return array
     */
    public function syncCandidates(?string $period = null, ?string $status = null): array
    {
        $baseUrl = self::getBaseUrl();
        $token = self::getApiToken();

        if (empty($baseUrl) || empty($token)) {
            return [
                'success' => false,
                'message' => 'URL atau Token API SPMB belum dikonfigurasi.',
                'synced_count' => 0,
            ];
        }

        $endpoint = "{$baseUrl}/api/v1/candidates";
        $page = 1;
        $syncedIds = [];
        $syncedCount = 0;
        $errors = [];

        try {
            do {
                $params = [
                    'per_page' => 50,
                    'page' => $page,
                ];
                if ($period && $period !== 'all') {
                    $params['period'] = $period;
                }
                if ($status && $status !== 'all') {
                    $params['status'] = $status;
                }

                $response = Http::withToken($token)
                    ->timeout(20)
                    ->acceptJson()
                    ->get($endpoint, $params);

                if (!$response->successful()) {
                    return [
                        'success' => false,
                        'message' => 'Gagal menarik data: ' . ($response->json('message') ?? 'HTTP ' . $response->status()),
                        'synced_count' => $syncedCount,
                    ];
                }

                $body = $response->json();
                $items = $body['data'] ?? [];

                foreach ($items as $item) {
                    try {
                        $candidate = SpmbCandidate::syncFromPayload($item);
                        $regId = $candidate->spmb_registration_id ?? ($item['id'] ?? null);
                        if ($regId) {
                            $syncedIds[] = (int) $regId;
                        }
                        $syncedCount++;
                    } catch (\Exception $ex) {
                        $errors[] = "No. Reg " . ($item['registration_number'] ?? '?') . ": " . $ex->getMessage();
                    }
                }

                $lastPage = $body['meta']['last_page'] ?? 1;
                $page++;
            } while ($page <= $lastPage);

            // Full Mirroring (Prune data yang tidak lagi diizinkan / tidak ada di SPMB)
            $syncedIds = array_values(array_filter(array_unique($syncedIds)));
            
            $pruneQuery = SpmbCandidate::query();
            if ($period && $period !== 'all') {
                $slashYear = str_replace('-', '/', $period);
                $hyphenYear = str_replace('/', '-', $period);
                $pruneQuery->where(function($q) use ($slashYear, $hyphenYear) {
                    $q->where('academic_year', $slashYear)
                      ->orWhere('academic_year', $hyphenYear);
                });
            }
            if (!empty($syncedIds)) {
                $pruneQuery->whereNotIn('spmb_registration_id', $syncedIds);
            }
            $prunedCount = $pruneQuery->delete();

            $periodLabel = $period && $period !== 'all' ? " TA " . str_replace('-', '/', $period) : "";
            if ($syncedCount > 0) {
                $msg = "Berhasil menarik {$syncedCount} data calon murid (Tahap Administrasi / Lunas) dari SPMB{$periodLabel}.";
            } else {
                $msg = "Sinkronisasi selesai. Belum ada calon murid baru pada tahap Administrasi / Lunas di SPMB{$periodLabel}.";
            }
            if ($prunedCount > 0) {
                $msg .= " ({$prunedCount} data yang tidak lagi memenuhi kriteria telah dibersihkan).";
            }

            return [
                'success' => true,
                'message' => $msg,
                'synced_count' => $syncedCount,
                'pruned_count' => $prunedCount,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            Log::error('SPMB sync candidates error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat sinkronisasi: ' . $e->getMessage(),
                'synced_count' => $syncedCount,
            ];
        }
    }

    /**
     * Verify incoming webhook signature.
     *
     * @param string $rawPayload
     * @param string|null $signatureHeader
     * @return bool
     */
    public function verifyWebhookSignature(string $rawPayload, ?string $signatureHeader): bool
    {
        $secret = self::getWebhookSecret();
        if (empty($secret)) {
            // If secret is not configured, deny webhook for security
            return false;
        }

        if (empty($signatureHeader)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $rawPayload, $secret);
        return hash_equals($expectedSignature, $signatureHeader);
    }

    /**
     * Process incoming webhook event payload.
     *
     * @param string $event
     * @param array $payload
     * @return array
     */
    public function processWebhookEvent(string $event, array $payload): array
    {
        Log::info("SPMB Webhook received event [{$event}]", ['payload' => $payload]);

        if ($event === 'ping') {
            return [
                'status' => 'pong',
                'message' => 'Webhook ping received successfully by SANS PAUD.',
                'timestamp' => now()->toIso8601String(),
            ];
        }

        // Candidate events
        if (in_array($event, ['candidate.agreement_signed', 'candidate.verified', 'candidate.accepted', 'candidate.created', 'payment.tuition_paid', 'payment.success', 'registration.completed'])) {
            $candidateData = $payload['data'] ?? $payload;
            $candidate = SpmbCandidate::syncFromPayload($candidateData);
            return [
                'status' => 'success',
                'event' => $event,
                'registration_number' => $candidate->registration_number,
                'full_name' => $candidate->full_name,
                'message' => "Calon murid {$candidate->full_name} ({$candidate->registration_number}) berhasil diperbarui.",
            ];
        }

        return [
            'status' => 'ignored',
            'message' => "Event {$event} tidak membutuhkan pemrosesan khusus.",
        ];
    }

    /**
     * Get dynamic master filter options from SPMB API with fallback.
     */
    public function getFilterOptions(): array
    {
        $baseUrl = self::getBaseUrl();
        $token = self::getApiToken();

        if (!empty($baseUrl) && !empty($token)) {
            try {
                $response = Http::withToken($token)
                    ->timeout(4)
                    ->acceptJson()
                    ->get("{$baseUrl}/api/v1/options");

                if ($response->successful()) {
                    $data = $response->json('data');
                    if (!empty($data) && is_array($data)) {
                        return $data;
                    }
                }
            } catch (\Throwable $e) {
                // Ignore and use master fallback
            }
        }

        // Fallback default master for PAUD (Relasi Unit PAUD -> Jenjang -> Kelas)
        return [
            'periods' => ['2027/2028', '2026/2027', '2028/2029', '2029/2030'],
            'registration_types' => ['Murid Baru', 'Mutasi Masuk / Pindahan'],
            'waves' => ['Indent', 'Gelombang 1', 'Gelombang 2'],
            'categories' => ['Reguler', 'Murid Berkebutuhan Khusus (MBK)'],
            'jenjangs' => [
                ['code' => 'KB', 'name' => 'Playgroup (KB)'],
                ['code' => 'TK', 'name' => 'Taman Kanak-kanak (TK)'],
                ['code' => 'TPA', 'name' => 'Daycare (TPA)'],
                ['code' => 'TPQ', 'name' => 'TPQ'],
            ],
            'grades' => [
                ['name' => 'KB A', 'jenjang_code' => 'KB'],
                ['name' => 'KB B', 'jenjang_code' => 'KB'],
                ['name' => 'TK A', 'jenjang_code' => 'TK'],
                ['name' => 'TK B', 'jenjang_code' => 'TK'],
                ['name' => 'TPA 1 (Umum)', 'jenjang_code' => 'TPA'],
                ['name' => 'TPA 1 (Anak Gukar YPAS)', 'jenjang_code' => 'TPA'],
                ['name' => 'TPA 2 (Mulai Usia 2 Tahun)', 'jenjang_code' => 'TPA'],
                ['name' => 'TPA 2 (Mulai Usia 3 Tahun)', 'jenjang_code' => 'TPA'],
                ['name' => 'TPA 3 (Mulai Usia 4 Tahun)', 'jenjang_code' => 'TPA'],
                ['name' => 'TPA 3 (Mulai Usia 5 Tahun)', 'jenjang_code' => 'TPA'],
                ['name' => 'TPQ', 'jenjang_code' => 'TPQ'],
            ],
        ];
    }
}
