<?php

namespace App\Http\Controllers;

use App\Models\Jenjang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JenjangController extends Controller
{
    /**
     * Display a listing of jenjang.
     */
    public function index(Request $request)
    {
        $jenjangs = Jenjang::orderBy('order', 'asc')->get();

        $totalJenjang = $jenjangs->count();
        $activeJenjang = $jenjangs->where('is_active', true)->count();

        $stats = [
            'total_jenjang' => $totalJenjang,
            'active_jenjang' => $activeJenjang,
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'jenjangs' => $jenjangs,
                'stats' => $stats,
            ]);
        }

        return view('admin.jenjangs.index', compact('jenjangs', 'stats'));
    }

    /**
     * Show single jenjang detail (JSON).
     */
    public function show($id): JsonResponse
    {
        $jenjang = Jenjang::findOrFail($id);

        return response()->json([
            'success' => true,
            'jenjang' => $jenjang,
        ]);
    }

    /**
     * Store new jenjang.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:30',
            'order' => 'nullable|integer|min:1|max:99',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        if (empty($validated['order'])) {
            $validated['order'] = (Jenjang::max('order') ?? 0) + 1;
        }

        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $jenjang = Jenjang::create($validated);

        return response()->json([
            'success' => true,
            'message' => "Jenjang {$jenjang->name} berhasil ditambahkan.",
            'jenjang' => $jenjang,
        ]);
    }

    /**
     * Update existing jenjang.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $jenjang = Jenjang::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:30',
            'order' => 'nullable|integer|min:1|max:99',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : $jenjang->is_active;

        $jenjang->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Jenjang {$jenjang->name} berhasil diperbarui.",
            'jenjang' => $jenjang,
        ]);
    }

    /**
     * Toggle active status of a jenjang.
     */
    public function toggleStatus($id): JsonResponse
    {
        $jenjang = Jenjang::findOrFail($id);
        $jenjang->is_active = !$jenjang->is_active;
        $jenjang->save();

        $statusText = $jenjang->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'success' => true,
            'message' => "Jenjang {$jenjang->name} berhasil {$statusText}.",
            'is_active' => $jenjang->is_active,
        ]);
    }

    /**
     * Delete jenjang.
     */
    public function destroy($id): JsonResponse
    {
        $jenjang = Jenjang::findOrFail($id);
        $name = $jenjang->name;
        $jenjang->delete();

        return response()->json([
            'success' => true,
            'message' => "Jenjang {$name} berhasil dihapus.",
        ]);
    }
}
