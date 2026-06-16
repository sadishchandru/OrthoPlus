<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExerciseSeeder extends Seeder
{
    public function run(): void
    {
        $exercises = [
            ['name' => 'Quad Sets', 'category' => 'Knee', 'instructions' => 'Tighten thigh muscles, hold 5 sec, relax. Repeat 10-15 times.', 'tags' => json_encode(['knee', 'quadriceps', 'beginner'])],
            ['name' => 'Straight Leg Raise', 'category' => 'Knee', 'instructions' => 'Lie flat, raise leg to 45°, hold 3 sec, lower slowly. 3 sets of 10.', 'tags' => json_encode(['knee', 'hip', 'quadriceps'])],
            ['name' => 'Heel Slides', 'category' => 'Knee', 'instructions' => 'Slide heel towards buttocks, hold 5 sec, return. 3 sets of 10.', 'tags' => json_encode(['knee', 'hamstrings', 'ROM'])],
            ['name' => 'Calf Raises', 'category' => 'Ankle', 'instructions' => 'Rise on tiptoes, hold 3 sec, lower slowly. 3 sets of 15.', 'tags' => json_encode(['ankle', 'calf', 'balance'])],
            ['name' => 'Ankle Circles', 'category' => 'Ankle', 'instructions' => 'Rotate ankle clockwise and counter-clockwise. 10 circles each direction.', 'tags' => json_encode(['ankle', 'ROM', 'mobility'])],
            ['name' => 'Shoulder Pendulum', 'category' => 'Shoulder', 'instructions' => 'Lean forward, let arm hang, swing in circles. 1 minute each direction.', 'tags' => json_encode(['shoulder', 'ROM', 'rotator cuff'])],
            ['name' => 'Chin Tucks', 'category' => 'Neck', 'instructions' => 'Gently pull chin back to create double chin, hold 5 sec. 10 reps.', 'tags' => json_encode(['neck', 'cervical', 'posture'])],
            ['name' => 'Pelvic Tilts', 'category' => 'Back', 'instructions' => 'Flatten lower back against floor, hold 5 sec, arch back slightly. 10 reps.', 'tags' => json_encode(['back', 'lumbar', 'core'])],
            ['name' => 'Bridge Exercise', 'category' => 'Back', 'instructions' => 'Lie on back, lift hips off floor, hold 5 sec, lower. 3 sets of 10.', 'tags' => json_encode(['back', 'glutes', 'core'])],
            ['name' => 'Knee-to-Chest Stretch', 'category' => 'Back', 'instructions' => 'Pull one knee to chest, hold 20-30 sec. Alternate legs. 3 reps each.', 'tags' => json_encode(['back', 'hip flexor', 'stretch'])],
            ['name' => 'Hip Abduction', 'category' => 'Hip', 'instructions' => 'Lie on side, raise top leg to 45°, hold 3 sec, lower. 3 sets of 10.', 'tags' => json_encode(['hip', 'abductors', 'glutes'])],
            ['name' => 'Wrist Flexion/Extension', 'category' => 'Wrist', 'instructions' => 'Bend wrist up and down, 10 reps each direction with light resistance.', 'tags' => json_encode(['wrist', 'forearm', 'ROM'])],
        ];

        foreach ($exercises as $e) {
            DB::table('exercises')->insertOrIgnore(array_merge($e, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
