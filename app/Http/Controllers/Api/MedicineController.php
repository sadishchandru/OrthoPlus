<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use Illuminate\Http\Request;

class MedicineController extends Controller
{
    public function index(Request $request)
    {
        $q = Medicine::query()
            ->when($request->search, fn($qq) => $qq->where('name', like_operator(), "%{$request->search}%")
                ->orWhere('generic_name', like_operator(), "%{$request->search}%"))
            ->orderBy('name');

        return response()->json($q->paginate(15));
    }

    /** Autocomplete for prescriptions/pharmacy — FULLTEXT (idx_med_search) with LIKE fallback. */
    public function search(Request $request)
    {
        $cols = ['id', 'name', 'generic_name', 'unit', 'strength', 'quantity', 'sell_price', 'hsn_code', 'expiry_date'];

        // Strip boolean-mode operators so user input can't break the query.
        $term = trim(preg_replace('/[+\-><()~*"@]+/', ' ', $request->get('q', '')));

        // FULLTEXT needs tokens >= ft_min_token_size (default 3). Shorter → LIKE.
        $words = array_filter(explode(' ', $term));
        $ftEligible = collect($words)->contains(fn($w) => strlen($w) >= 3);

        // FULLTEXT is MySQL-only — skip it on other drivers (SQLite/Postgres) → LIKE.
        if ($ftEligible && \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'mysql') {
            $expr = collect($words)->map(fn($w) => '+' . $w . '*')->implode(' ');
            $meds = Medicine::where('status', 'active')
                ->whereRaw('MATCH(name, generic_name) AGAINST(? IN BOOLEAN MODE)', [$expr])
                ->limit(15)
                ->get($cols);
            if ($meds->isNotEmpty()) {
                return response()->json($meds);
            }
        }

        // Fallback: LIKE (short query or no FULLTEXT hits)
        $like = '%' . $term . '%';
        $meds = Medicine::where('status', 'active')
            ->where(fn($qq) => $qq->where('name', like_operator(), $like)
                ->orWhere('generic_name', like_operator(), $like))
            ->limit(15)
            ->get($cols);

        return response()->json($meds);
    }

    public function store(Request $request)
    {
        $medicine = Medicine::create($this->validateData($request));
        return response()->json($medicine, 201);
    }

    public function update(Request $request, Medicine $medicine)
    {
        $medicine->update($this->validateData($request));
        return response()->json($medicine);
    }

    public function destroy(Medicine $medicine)
    {
        $medicine->update(['status' => 'discontinued']);
        return response()->json(['message' => 'Discontinued']);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name'          => 'required|string|max:255',
            'generic_name'  => 'nullable|string|max:255',
            'manufacturer'  => 'nullable|string|max:255',
            'unit'          => 'nullable|string|max:20',
            'strength'      => 'nullable|string|max:50',
            'quantity'      => 'nullable|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'expiry_date'   => 'nullable|date',
            'cost_price'    => 'nullable|numeric|min:0',
            'sell_price'    => 'nullable|numeric|min:0',
            'hsn_code'      => 'nullable|string|max:20',
            'status'        => 'nullable|in:active,discontinued',
        ]);
    }
}
