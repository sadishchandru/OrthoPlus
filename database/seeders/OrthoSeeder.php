<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Therapist;
use App\Models\Medicine;
use App\Models\SoapTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OrthoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRolesAndUsers();
        $this->seedTherapists();
        $this->seedMedicines();
        $this->seedSoapTemplates();
    }

    private function seedRolesAndUsers(): void
    {
        $roles = [
            'root'         => 'Root / Admin',
            'doctor'       => 'Doctor',
            'front_office' => 'Front Office',
            'billing'      => 'Billing',
            'pharmacy'     => 'Pharmacy',
            'therapist'    => 'Therapist',
        ];
        foreach ($roles as $name => $label) {
            Role::firstOrCreate(['name' => $name], ['label' => $label, 'guard_name' => 'web']);
        }

        // username => [display name, password, role]
        $users = [
            'root'        => ['Root Admin',   'root123', 'root'],
            'doctor'      => ['Dr. Default',  'doc123',  'doctor'],
            'frontoffice' => ['Front Office', 'fo123',   'front_office'],
            'billing'     => ['Billing Desk', 'bill123', 'billing'],
            'pharmacy'    => ['Pharmacy',     'ph123',   'pharmacy'],
            'therapist'   => ['Therapist',    'th123',   'therapist'],
        ];
        foreach ($users as $username => [$name, $pass, $role]) {
            $user = User::updateOrCreate(
                ['username' => $username],
                [
                    'name'     => $name,
                    'email'    => $username . '@ortho.local',
                    'password' => Hash::make($pass),
                ]
            );
            $roleId = Role::where('name', $role)->value('id');
            $user->roles()->syncWithoutDetaching([$roleId]);
        }
    }

    private function seedTherapists(): void
    {
        $sched = [
            ['day' => 'Mon', 'start' => '09:00', 'end' => '17:00', 'break_start' => '13:00', 'break_end' => '14:00'],
            ['day' => 'Tue', 'start' => '09:00', 'end' => '17:00', 'break_start' => '13:00', 'break_end' => '14:00'],
            ['day' => 'Wed', 'start' => '09:00', 'end' => '17:00', 'break_start' => '13:00', 'break_end' => '14:00'],
            ['day' => 'Thu', 'start' => '09:00', 'end' => '17:00', 'break_start' => '13:00', 'break_end' => '14:00'],
            ['day' => 'Fri', 'start' => '09:00', 'end' => '17:00', 'break_start' => '13:00', 'break_end' => '14:00'],
        ];
        $therapists = [
            ['name' => 'Ramesh Kumar', 'phone' => '9876543210', 'email' => 'ramesh@ortho.local', 'specialization' => 'Musculoskeletal Physiotherapy', 'commission_pct' => 30],
            ['name' => 'Priya Nair',   'phone' => '9876500011', 'email' => 'priya@ortho.local',  'specialization' => 'Sports Rehabilitation',         'commission_pct' => 35],
            ['name' => 'Arjun Menon',  'phone' => '9876500022', 'email' => 'arjun@ortho.local',  'specialization' => 'Neuro Physiotherapy',           'commission_pct' => 30],
        ];
        foreach ($therapists as $t) {
            Therapist::updateOrCreate(
                ['name' => $t['name']],
                array_merge($t, ['schedule' => $sched, 'is_active' => true])
            );
        }
    }

    private function seedMedicines(): void
    {
        $meds = [
            ['name' => 'Paracetamol 500mg', 'generic_name' => 'Paracetamol', 'manufacturer' => 'Cipla',    'unit' => 'tablet', 'strength' => '500mg', 'quantity' => 500, 'reorder_level' => 50, 'cost_price' => 0.5, 'sell_price' => 1.0,  'hsn_code' => '3004'],
            ['name' => 'Ibuprofen 400mg',   'generic_name' => 'Ibuprofen',   'manufacturer' => 'Sun Pharma','unit' => 'tablet', 'strength' => '400mg', 'quantity' => 300, 'reorder_level' => 40, 'cost_price' => 0.8, 'sell_price' => 1.5,  'hsn_code' => '3004'],
            ['name' => 'Diclofenac Gel',     'generic_name' => 'Diclofenac',  'manufacturer' => 'Novartis', 'unit' => 'cream',  'strength' => '1%',    'quantity' => 80,  'reorder_level' => 15, 'cost_price' => 45,  'sell_price' => 75,   'hsn_code' => '3004'],
            ['name' => 'Calcium + D3',       'generic_name' => 'Calcium Carbonate', 'manufacturer' => 'Abbott', 'unit' => 'tablet', 'strength' => '500mg', 'quantity' => 200, 'reorder_level' => 30, 'cost_price' => 2,   'sell_price' => 4,    'hsn_code' => '3004'],
            ['name' => 'Methylcobalamin',    'generic_name' => 'Mecobalamin', 'manufacturer' => 'Mankind',  'unit' => 'tablet', 'strength' => '1500mcg','quantity' => 150, 'reorder_level' => 25, 'cost_price' => 3,   'sell_price' => 6,    'hsn_code' => '3004'],
        ];
        foreach ($meds as $m) {
            Medicine::updateOrCreate(
                ['name' => $m['name']],
                array_merge($m, ['status' => 'active'])
            );
        }
    }

    private function seedSoapTemplates(): void
    {
        $templates = [
            [
                'name'       => 'Low Back Pain',
                'subjective' => 'Patient reports lower back pain, duration and aggravating factors noted.',
                'objective'  => 'Lumbar ROM restricted. Tenderness on palpation. SLR test recorded.',
                'assessment' => 'Mechanical low back pain / lumbar strain.',
                'plan'       => 'IFT + lumbar mobilization. Core strengthening HEP. Review in 1 week.',
            ],
            [
                'name'       => 'Frozen Shoulder',
                'subjective' => 'Progressive shoulder stiffness and night pain.',
                'objective'  => 'Reduced shoulder ROM in all planes, capsular pattern.',
                'assessment' => 'Adhesive capsulitis.',
                'plan'       => 'Ultrasound + capsular stretching. Pendular exercises. Review weekly.',
            ],
            [
                'name'       => 'Knee Osteoarthritis',
                'subjective' => 'Knee pain on stairs/standing, morning stiffness.',
                'objective'  => 'Crepitus, mild effusion, quadriceps wasting, reduced flexion.',
                'assessment' => 'Knee osteoarthritis.',
                'plan'       => 'TENS + quadriceps strengthening. Weight advice. Review in 2 weeks.',
            ],
        ];
        foreach ($templates as $tpl) {
            SoapTemplate::updateOrCreate(['name' => $tpl['name']], $tpl);
        }
    }
}
