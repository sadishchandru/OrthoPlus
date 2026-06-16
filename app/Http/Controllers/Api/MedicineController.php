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
            ->when($request->search, fn($qq) => $qq->where('name', 'like', "%{$request->search}%")
                ->orWhere('generic_name', 'like', "%{$request->search}%"))
            ->orderBy('name');

        return response()->json($q->paginate(15));
    }

    /** Autocomplete for prescriptions/pharmacy. */
    public function search(Request $request)
    {
        $term = $request->get('q', '');
        $meds = Medicine::where('status', 'active')
            ->where(fn($qq) => $qq->where('name', 'like', "%$term%")
                ->orWhere('generic_name', 'like', "%$term%"))
            ->limit(15)
            ->get(['id', 'name', 'generic_name', 'unit', 'strength', 'quantity', 'sell_price']);

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
