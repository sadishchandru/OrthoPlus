<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TreatmentCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $treatments = [
            ['name' => 'TENS Therapy', 'category' => 'Electrotherapy', 'duration_min' => 20, 'price' => 300],
            ['name' => 'Ultrasound Therapy', 'category' => 'Electrotherapy', 'duration_min' => 15, 'price' => 350],
            ['name' => 'IFT Therapy', 'category' => 'Electrotherapy', 'duration_min' => 20, 'price' => 300],
            ['name' => 'Wax Bath', 'category' => 'Hydrotherapy', 'duration_min' => 20, 'price' => 250],
            ['name' => 'Hot Pack', 'category' => 'Thermotherapy', 'duration_min' => 15, 'price' => 150],
            ['name' => 'Cold Pack', 'category' => 'Cryotherapy', 'duration_min' => 15, 'price' => 150],
            ['name' => 'Manual Therapy', 'category' => 'Manual', 'duration_min' => 30, 'price' => 500],
            ['name' => 'Joint Mobilization', 'category' => 'Manual', 'duration_min' => 30, 'price' => 600],
            ['name' => 'Exercise Therapy', 'category' => 'Exercise', 'duration_min' => 45, 'price' => 400],
            ['name' => 'Traction Therapy', 'category' => 'Traction', 'duration_min' => 20, 'price' => 350],
            ['name' => 'Orthopedic Consultation', 'category' => 'Consultation', 'duration_min' => 30, 'price' => 800],
            ['name' => 'Physiotherapy Assessment', 'category' => 'Consultation', 'duration_min' => 60, 'price' => 600],
            ['name' => 'Kinesio Taping', 'category' => 'Taping', 'duration_min' => 20, 'price' => 400],
            ['name' => 'Dry Needling', 'category' => 'Needling', 'duration_min' => 30, 'price' => 700],
            ['name' => 'Gait Training', 'category' => 'Exercise', 'duration_min' => 45, 'price' => 500],
        ];

        // Match on name → idempotent on every driver. insertOrIgnore only dedupes
        // with a UNIQUE index (treatment_catalog.name has none) → it dup'd each deploy.
        foreach ($treatments as $t) {
            DB::table('treatment_catalog')->updateOrInsert(
                ['name' => $t['name']],
                array_merge($t, ['is_active' => true, 'updated_at' => now(), 'created_at' => now()])
            );
        }
    }
}
