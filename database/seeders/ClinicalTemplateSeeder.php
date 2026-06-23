<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClinicalTemplate;

class ClinicalTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // Examination
            ['name' => 'Knee Joint Examination', 'specialty' => 'knee', 'type' => 'examination',
             'fields' => ['ROM', 'Swelling', 'Crepitus', 'Ligament Tests', 'Meniscus Tests', 'Patella Tracking', 'X-ray Findings']],
            ['name' => 'Hip Joint Examination', 'specialty' => 'hip', 'type' => 'examination',
             'fields' => ['ROM', 'FABER', 'FADIR', 'Trendelenburg', 'Leg Length Discrepancy', 'Harris Hip Score']],
            ['name' => 'Shoulder Examination', 'specialty' => 'shoulder', 'type' => 'examination',
             'fields' => ['ROM', 'Rotator Cuff Tests', 'Impingement Tests', 'Instability Tests', 'Hawkins', 'Neer']],
            ['name' => 'Spine Examination', 'specialty' => 'spine', 'type' => 'examination',
             'fields' => ['Cervical ROM', 'Lumbar ROM', 'SLR', 'Neurological', 'Spurling', 'Reflexes']],
            ['name' => 'Fracture Assessment', 'specialty' => 'fracture', 'type' => 'examination',
             'fields' => ['Fracture Type', 'Location', 'Displacement', 'Angulation', 'X-ray Grade', 'Neurovascular Status']],

            // Operative Notes
            ['name' => 'Total Knee Replacement', 'specialty' => 'knee', 'type' => 'operative_note',
             'fields' => ['Approach', 'Implant Used', 'Component Sizes', 'Alignment', 'Blood Loss', 'Tourniquet Time']],
            ['name' => 'Total Hip Replacement', 'specialty' => 'hip', 'type' => 'operative_note',
             'fields' => ['Approach', 'Cup Size', 'Stem Size', 'Head Size', 'Offset', 'Leg Lengths', 'Bearing Surface']],
            ['name' => 'ORIF Fracture', 'specialty' => 'fracture', 'type' => 'operative_note',
             'fields' => ['Fracture Pattern', 'Approach', 'Implant', 'Screw Configuration', 'Reduction Quality', 'Fluoroscopy Used']],
            ['name' => 'Spine Fusion', 'specialty' => 'spine', 'type' => 'operative_note',
             'fields' => ['Level', 'Approach', 'Cage Used', 'Pedicle Screws', 'Rod Size', 'Bone Graft', 'Neuromonitoring']],

            // Discharge
            ['name' => 'Post-TKR Discharge', 'specialty' => 'knee', 'type' => 'discharge',
             'fields' => ['Weight Bearing Status', 'Knee ROM at Discharge', 'DVT Prophylaxis', 'Exercises', 'Follow-up']],
            ['name' => 'Fracture Discharge', 'specialty' => 'fracture', 'type' => 'discharge',
             'fields' => ['Weight Bearing', 'Cast Instructions', 'Danger Signs', 'X-ray Review Date', 'Physiotherapy']],
        ];

        foreach ($templates as $t) {
            ClinicalTemplate::updateOrCreate(
                ['name' => $t['name']],
                [
                    'specialty'     => $t['specialty'],
                    'template_type' => $t['type'],   // spec key 'type' → column 'template_type'
                    'fields'        => $t['fields'],
                    'is_active'     => true,
                ]
            );
        }
    }
}
