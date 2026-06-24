<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChargeMaster;
use Illuminate\Http\Request;

class ChargeMasterController extends Controller
{
    public function index(Request $request)
    {
        $charges = ChargeMaster::query()
            ->when($request->filled('category'), fn($q) => $q->where('category', $request->category))
            ->when($request->boolean('active_only'), fn($q) => $q->where('is_active', true))
            ->orderBy('category')->orderBy('name')
            ->get();

        return response()->json($charges);
    }

    public function store(Request $request)
    {
        // Upsert by id when present, otherwise create.
        $data = $request->validate([
            'id'            => 'nullable|exists:charge_master,id',
            'name'          => 'required|string|max:255',
            'category'      => 'required|in:consultation,procedure,room,lab,pharmacy,surgery',
            'charge_amount' => 'required|numeric|min:0',
            'gst_pct'       => 'nullable|numeric|min:0|max:100',
            'is_active'     => 'boolean',
        ]);

        $charge = ChargeMaster::updateOrCreate(
            ['id' => $data['id'] ?? null],
            collect($data)->except('id')->toArray()
        );

        return response()->json($charge, $charge->wasRecentlyCreated ? 201 : 200);
    }

    public function update(Request $request, ChargeMaster $charge_master)
    {
        $data = $request->validate([
            'name'          => 'sometimes|required|string|max:255',
            'category'      => 'sometimes|required|in:consultation,procedure,room,lab,pharmacy,surgery,implant,misc',
            'charge_amount' => 'sometimes|required|numeric|min:0',
            'gst_pct'       => 'nullable|numeric|min:0|max:100',
            'is_active'     => 'boolean',
        ]);
        $charge_master->update($data);
        return response()->json($charge_master);
    }

    public function destroy(ChargeMaster $charge_master)
    {
        $charge_master->delete();
        return response()->json(['deleted' => true]);
    }
}
