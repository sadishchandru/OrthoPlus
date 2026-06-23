<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Models\ExercisePrescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExerciseController extends Controller
{
    /** Joint categories used for ROM dropdown + exercise filtering. */
    public const JOINTS = ['Cervical', 'Shoulder', 'Elbow', 'Wrist', 'Hip', 'Knee', 'Ankle', 'Lumbar', 'Thoracic'];

    public function index(Request $request)
    {
        $exercises = Exercise::query()
            ->when($request->q, fn($q) => $q->where('name', like_operator(), "%{$request->q}%")
                ->orWhere('category', like_operator(), "%{$request->q}%"))
            ->when($request->category, fn($q) => $q->where('category', $request->category))
            ->when(!$request->boolean('all'), fn($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->paginate(20);

        return response()->json($exercises);
    }

    /** Distinct joint/category list — feeds ROM joint dropdown + Exercise Library. */
    public function categories()
    {
        $used = Exercise::where('is_active', true)
            ->whereNotNull('category')->distinct()->pluck('category')->all();
        $merged = collect(self::JOINTS)->merge($used)->unique()->values();
        return response()->json($merged);
    }

    public function store(Request $request)
    {
        $exercise = Exercise::create($this->validateData($request));
        return response()->json($exercise, 201);
    }

    public function update(Request $request, Exercise $exercise)
    {
        $exercise->update($this->validateData($request));
        return response()->json($exercise);
    }

    public function destroy(Exercise $exercise)
    {
        $exercise->delete();
        return response()->json(['message' => 'Deleted']);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name'         => 'required|string|max:255',
            'category'     => 'nullable|string|max:100',
            'instructions' => 'nullable|string',
            'image_url'    => 'nullable|string|max:1000',
            'video_url'    => 'nullable|string|max:1000',
            'tags'         => 'nullable|array',
            'tags.*'       => 'string|max:50',
            'is_active'    => 'boolean',
        ]);
    }

    public function prescribe(Request $request)
    {
        $data = $request->validate([
            'patient_id'         => 'required|exists:patients,id',
            'clinical_record_id' => 'nullable|exists:clinical_records,id',
            'exercises'          => 'required|array|min:1',
            'exercises.*.exercise_id' => 'required|exists:exercises,id',
            'exercises.*.sets'   => 'nullable|integer',
            'exercises.*.reps'   => 'nullable|integer',
            'exercises.*.hold'   => 'nullable|integer',
            'exercises.*.notes'  => 'nullable|string',
            'frequency'          => 'nullable|string',
            'notes'              => 'nullable|string',
        ]);

        $prescription = ExercisePrescription::create(array_merge($data, [
            'created_by' => Auth::id(),
        ]));

        return response()->json($prescription, 201);
    }
}
