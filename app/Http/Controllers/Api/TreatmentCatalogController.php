<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TreatmentCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TreatmentCatalogController extends Controller
{
    /** Admin list — paginated/searchable. */
    public function index(Request $request)
    {
        $q = TreatmentCatalog::query()
            ->when($request->search, fn($qq) => $qq->where('name', like_operator(), "%{$request->search}%"))
            ->orderBy('name');

        return response()->json($q->paginate(15));
    }

    /** Public active list (dropdowns). Cached 60 min. */
    public function active()
    {
        $list = Cache::remember('treatment_catalog.active', 3600, fn() =>
            TreatmentCatalog::where('is_active', true)->orderBy('name')->get()
        );
        return response()->json($list);
    }

    public function store(Request $request)
    {
        $row = TreatmentCatalog::create($this->validateData($request));
        Cache::forget('treatment_catalog.active');
        return response()->json($row, 201);
    }

    public function update(Request $request, TreatmentCatalog $treatmentCatalog)
    {
        $treatmentCatalog->update($this->validateData($request));
        Cache::forget('treatment_catalog.active');
        return response()->json($treatmentCatalog);
    }

    public function destroy(TreatmentCatalog $treatmentCatalog)
    {
        $treatmentCatalog->delete();
        Cache::forget('treatment_catalog.active');
        return response()->json(['message' => 'Deleted']);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name'         => 'required|string|max:255',
            'category'     => 'nullable|string|max:100',
            'duration_min' => 'nullable|integer|min:0',
            'price'        => 'nullable|numeric|min:0',
            'description'  => 'nullable|string',
            'is_active'    => 'boolean',
        ]);
    }
}
