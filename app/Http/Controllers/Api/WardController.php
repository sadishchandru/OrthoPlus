<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ward;
use Illuminate\Http\Request;

class WardController extends Controller
{
    public function index(Request $request)
    {
        $wards = Ward::query()
            ->when($request->boolean('active_only'), fn($q) => $q->where('is_active', true))
            ->withCount([
                'beds',
                'beds as occupied_beds_count' => fn($q) => $q->where('status', 'occupied'),
                'beds as available_beds_count' => fn($q) => $q->where('status', 'available'),
            ])
            ->orderBy('name')
            ->get();

        return response()->json($wards);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'type'       => 'nullable|string|max:50',
            'floor'      => 'nullable|string|max:50',
            'total_beds' => 'nullable|integer|min:0',
            'is_active'  => 'boolean',
        ]);

        return response()->json(Ward::create($data), 201);
    }

    public function show(Ward $ward)
    {
        return response()->json($ward->load('beds'));
    }

    public function update(Request $request, Ward $ward)
    {
        $data = $request->validate([
            'name'       => 'sometimes|required|string|max:255',
            'type'       => 'nullable|string|max:50',
            'floor'      => 'nullable|string|max:50',
            'total_beds' => 'nullable|integer|min:0',
            'is_active'  => 'boolean',
        ]);

        $ward->update($data);
        return response()->json($ward);
    }

    public function destroy(Ward $ward)
    {
        $ward->delete();
        return response()->json(['deleted' => true]);
    }

    /** Beds belonging to a ward. */
    public function beds(Ward $ward)
    {
        return response()->json($ward->beds()->orderBy('bed_number')->get());
    }
}
