<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ward;
use App\Models\Bed;
use App\Models\ChargeMaster;

class HospitalSeeder extends Seeder
{
    public function run(): void
    {
        // Wards (idempotent by name)
        $wards = [
            ['name' => 'General Ward',  'type' => 'general',      'floor' => 'Ground', 'total_beds' => 20],
            ['name' => 'ICU',           'type' => 'icu',          'floor' => '1st',    'total_beds' => 6],
            ['name' => 'Private Ward',  'type' => 'private',      'floor' => '2nd',    'total_beds' => 10],
            ['name' => 'Semi-Private',  'type' => 'semi-private', 'floor' => '2nd',    'total_beds' => 12],
            ['name' => 'Emergency',     'type' => 'emergency',    'floor' => 'Ground', 'total_beds' => 4],
        ];
        foreach ($wards as $w) {
            Ward::updateOrCreate(['name' => $w['name']], $w);
        }

        // Beds per ward (idempotent by ward_id + bed_number)
        foreach (Ward::all() as $ward) {
            $prefix = strtoupper(substr($ward->type, 0, 1));
            for ($i = 1; $i <= $ward->total_beds; $i++) {
                Bed::updateOrCreate(
                    ['ward_id' => $ward->id, 'bed_number' => $prefix . str_pad($i, 2, '0', STR_PAD_LEFT)],
                    [
                        'bed_type'     => $ward->type === 'icu' ? 'icu' : 'standard',
                        'daily_charge' => match ($ward->type) {
                            'icu'          => 5000,
                            'private'      => 3000,
                            'semi-private' => 1500,
                            default        => 800,
                        },
                    ]
                );
            }
        }

        // Charge master defaults (idempotent by name)
        $charges = [
            ['name' => 'Consultation Fee',     'category' => 'consultation', 'charge_amount' => 500],
            ['name' => 'General Ward Charge',  'category' => 'room',         'charge_amount' => 800],
            ['name' => 'ICU Charge',           'category' => 'room',         'charge_amount' => 5000],
            ['name' => 'Private Room Charge',  'category' => 'room',         'charge_amount' => 3000],
            ['name' => 'Dressing',             'category' => 'procedure',    'charge_amount' => 200],
            ['name' => 'IV Cannula Insertion', 'category' => 'procedure',    'charge_amount' => 150],
            ['name' => 'X-Ray',                'category' => 'lab',          'charge_amount' => 400],
            ['name' => 'MRI Knee',             'category' => 'lab',          'charge_amount' => 8000],
            ['name' => 'Blood Test (CBC)',     'category' => 'lab',          'charge_amount' => 300],
            ['name' => 'Surgery - Minor',      'category' => 'surgery',      'charge_amount' => 15000],
            ['name' => 'Surgery - Major',      'category' => 'surgery',      'charge_amount' => 50000],
            ['name' => 'TKR (Total Knee)',     'category' => 'surgery',      'charge_amount' => 120000],
            ['name' => 'THR (Total Hip)',      'category' => 'surgery',      'charge_amount' => 130000],
        ];
        foreach ($charges as $c) {
            ChargeMaster::updateOrCreate(['name' => $c['name']], $c);
        }
    }
}
