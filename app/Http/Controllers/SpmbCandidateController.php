<?php

namespace App\Http\Controllers;

use App\Models\SpmbCandidate;
use App\Services\SpmbIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpmbCandidateController extends Controller
{
    protected SpmbIntegrationService $service;

    public function __construct(SpmbIntegrationService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of SPMB Candidates.
     */
    public function index(Request $request)
    {
        // 1. Get available academic years
        $academicYears = SpmbCandidate::select('academic_year')
            ->whereNotNull('academic_year')
            ->distinct()
            ->orderBy('academic_year', 'desc')
            ->pluck('academic_year')
            ->toArray();

        // Default to latest year or 'all' if empty
        $selectedYear = $request->get('period', $academicYears[0] ?? 'all');

        // 2. Base Query
        $query = SpmbCandidate::query();

        if ($selectedYear && $selectedYear !== 'all') {
            $query->where('academic_year', $selectedYear);
        }

        // Search Filter
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('registration_number', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%")
                  ->orWhere('father_name', 'like', "%{$search}%")
                  ->orWhere('mother_name', 'like', "%{$search}%")
                  ->orWhere('parent_phone', 'like', "%{$search}%");
            });
        }

        // Status Filter
        if ($status = $request->get('status')) {
            if ($status !== 'all') {
                $query->where('registration_status', $status);
            }
        }

        // Payment Filter
        if ($payment = $request->get('payment_status')) {
            if ($payment !== 'all') {
                $query->where('payment_status', $payment);
            }
        }

        // Wave Filter
        if ($wave = $request->get('wave')) {
            if ($wave !== 'all') {
                $query->where('wave', $wave);
            }
        }

        // 3. Stats Calculation (based on selected year)
        $statsQuery = SpmbCandidate::query();
        if ($selectedYear && $selectedYear !== 'all') {
            $statsQuery->where('academic_year', $selectedYear);
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'verified' => (clone $statsQuery)->whereIn('registration_status', ['verified', 'accepted', 'diterima', 'terverifikasi'])->count(),
            'paid' => (clone $statsQuery)->whereIn('payment_status', ['paid', 'lunas', 'settlement', 'success'])->count(),
            'enrolled' => (clone $statsQuery)->where('is_enrolled', true)->count(),
        ];

        // 4. Get available waves for filter dropdown
        $availableWaves = (clone $statsQuery)->whereNotNull('wave')->distinct()->pluck('wave')->toArray();

        $candidates = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('admin.spmb_candidates', compact('candidates', 'academicYears', 'selectedYear', 'stats', 'availableWaves'));
    }

    /**
     * Show detail of candidate.
     */
    public function show($id): JsonResponse
    {
        $candidate = SpmbCandidate::findOrFail($id);
        return response()->json([
            'success' => true,
            'candidate' => $candidate,
            'clean_phone' => $candidate->getCleanPhone(),
            'wa_url' => $candidate->whatsapp_url,
        ]);
    }

    /**
     * Trigger manual pull sync from SPMB.
     */
    public function sync(Request $request): JsonResponse
    {
        $period = $request->input('period');
        $status = $request->input('status');

        $result = $this->service->syncCandidates($period, $status);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Test SPMB connection endpoint.
     */
    public function testConnection(): JsonResponse
    {
        $result = $this->service->testConnection();
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Toggle enrollment status to active student.
     */
    public function toggleEnroll($id): JsonResponse
    {
        $candidate = SpmbCandidate::findOrFail($id);
        $candidate->is_enrolled = !$candidate->is_enrolled;
        $candidate->enrolled_at = $candidate->is_enrolled ? now() : null;
        $candidate->save();

        return response()->json([
            'success' => true,
            'is_enrolled' => $candidate->is_enrolled,
            'message' => $candidate->is_enrolled 
                ? "Calon murid {$candidate->full_name} berhasil ditandai sebagai Siswa Aktif." 
                : "Status Siswa Aktif untuk {$candidate->full_name} dibatalkan.",
        ]);
    }
}
