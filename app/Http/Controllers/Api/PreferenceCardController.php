<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PreferenceCard;
use Illuminate\Http\Request;

class PreferenceCardController extends Controller
{
    public function index(Request $request)
    {
        $cards = PreferenceCard::query()
            ->when($request->filled('surgeon_id'), fn($q) => $q->where('surgeon_id', $request->surgeon_id))
            ->when($request->filled('q'), fn($q) => $q->where('surgery_name', like_operator(), '%' . $request->q . '%'))
            ->orderBy('surgery_name')
            ->get();

        return response()->json($cards);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'surgery_name'         => 'required|string|max:255',
            'surgeon_id'           => 'nullable|integer',
            'instruments'          => 'nullable|array',
            'implants'             => 'nullable|array',
            'draping_notes'        => 'nullable|string',
            'special_requirements' => 'nullable|string',
        ]);

        $data['created_by'] = auth()->id();
        return response()->json(PreferenceCard::create($data), 201);
    }

    public function show(PreferenceCard $preference_card)
    {
        return response()->json($preference_card);
    }

    public function update(Request $request, PreferenceCard $preference_card)
    {
        $data = $request->validate([
            'surgery_name'         => 'sometimes|required|string|max:255',
            'surgeon_id'           => 'nullable|integer',
            'instruments'          => 'nullable|array',
            'implants'             => 'nullable|array',
            'draping_notes'        => 'nullable|string',
            'special_requirements' => 'nullable|string',
        ]);

        $preference_card->update($data);
        return response()->json($preference_card);
    }

    public function destroy(PreferenceCard $preference_card)
    {
        $preference_card->delete();
        return response()->json(['deleted' => true]);
    }
}
