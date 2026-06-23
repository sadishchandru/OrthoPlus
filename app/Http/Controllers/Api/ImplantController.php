<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Implant;
use Illuminate\Http\Request;

class ImplantController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $implants = Implant::query()
            ->when($q, fn($query) => $query->where(function ($w) use ($q) {
                $w->where('name', like_operator(), "%$q%")
                  ->orWhere('ref_number', like_operator(), "%$q%")
                  ->orWhere('manufacturer', like_operator(), "%$q%");
            }))
            ->when($request->boolean('active_only'), fn($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get();

        return response()->json($implants);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'manufacturer'  => 'nullable|string|max:255',
            'ref_number'    => 'nullable|string|max:100',
            'size'          => 'nullable|string|max:50',
            'side'          => 'nullable|in:left,right,bilateral',
            'quantity'      => 'nullable|integer|min:0',
            'unit_cost'     => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'expiry_date'   => 'nullable|date',
            'batch_number'  => 'nullable|string|max:100',
            'reorder_level' => 'nullable|integer|min:0',
            'is_active'     => 'boolean',
        ]);

        return response()->json(Implant::create($data), 201);
    }

    public function show(Implant $implant)
    {
        return response()->json($implant);
    }

    public function update(Request $request, Implant $implant)
    {
        $data = $request->validate([
            'name'          => 'sometimes|required|string|max:255',
            'manufacturer'  => 'nullable|string|max:255',
            'ref_number'    => 'nullable|string|max:100',
            'size'          => 'nullable|string|max:50',
            'side'          => 'nullable|in:left,right,bilateral',
            'quantity'      => 'nullable|integer|min:0',
            'unit_cost'     => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'expiry_date'   => 'nullable|date',
            'batch_number'  => 'nullable|string|max:100',
            'reorder_level' => 'nullable|integer|min:0',
            'is_active'     => 'boolean',
        ]);

        $implant->update($data);
        return response()->json($implant);
    }

    public function destroy(Implant $implant)
    {
        $implant->delete();
        return response()->json(['deleted' => true]);
    }

    /** Adjust stock by a delta (+ received, - consumed/wastage). */
    public function adjust(Request $request, Implant $implant)
    {
        $data = $request->validate([
            'delta'  => 'required|integer',
            'reason' => 'nullable|string|max:255',
        ]);

        $implant->quantity = max(0, $implant->quantity + $data['delta']);
        $implant->save();

        return response()->json($implant);
    }

    /** Implants at or below their reorder level. */
    public function lowStock()
    {
        $low = Implant::query()
            ->where('is_active', true)
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->orderBy('quantity')
            ->get();

        return response()->json($low);
    }
}
