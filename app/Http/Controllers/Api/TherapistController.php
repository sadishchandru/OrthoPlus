<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Therapist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TherapistController extends Controller
{
    /** Admin list — all therapists, paginated/searchable. */
    public function index(Request $request)
    {
        $q = Therapist::query()
            ->when($request->search, fn($qq) => $qq->where('name', like_operator(), "%{$request->search}%")
                ->orWhere('specialization', like_operator(), "%{$request->search}%"))
            ->orderBy('name');

        return response()->json($q->paginate(15));
    }

    /** Public active list (dropdowns). Cached 60 min. */
    public function active()
    {
        $list = Cache::remember('therapists.active', 3600, fn() =>
            Therapist::where('is_active', true)->orderBy('name')->get()
        );
        return response()->json($list);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $therapist = Therapist::create($data);
        Cache::forget('therapists.active');
        return response()->json($therapist, 201);
    }

    public function update(Request $request, Therapist $therapist)
    {
        $data = $this->validateData($request, $therapist->id);
        $therapist->update($data);
        Cache::forget('therapists.active');
        return response()->json($therapist);
    }

    public function destroy(Therapist $therapist)
    {
        $therapist->delete();
        Cache::forget('therapists.active');
        return response()->json(['message' => 'Deleted']);
    }

    private function validateData(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name'                  => 'required|string|max:255',
            'phone'                 => 'nullable|string|max:30',
            'email'                 => 'nullable|email|max:255',
            'specialization'        => 'nullable|string|max:255',
            'commission_pct'        => 'nullable|numeric|min:0|max:100',
            'schedule'              => 'nullable|array',
            'schedule.*.day'        => 'required_with:schedule|string',
            'schedule.*.start'      => 'nullable|string',
            'schedule.*.end'        => 'nullable|string',
            'schedule.*.break_start'=> 'nullable|string',
            'schedule.*.break_end'  => 'nullable|string',
            'is_active'             => 'boolean',
        ]);
    }
}
