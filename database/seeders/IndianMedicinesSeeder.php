<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * IndianMedicinesSeeder — ~520 common Indian medicines for an orthopaedic clinic.
 *
 * NOTE: the `medicines` table uses cost_price / sell_price (NOT purchase_price /
 * selling_price) and has a status enum. Public drug datasets are dead (404), so this
 * generates a realistic catalogue from curated brand×strength tuples plus
 * generic-maker variants. Idempotent: skips names already present (name has no UNIQUE
 * key), then chunked insertOrIgnore (50 at a time).
 */
class IndianMedicinesSeeder extends Seeder
{
    private array $makers = [
        'Aurobindo', 'Hetero', 'Macleods', 'Mankind', 'Torrent', 'Zydus', 'Lupin',
        'Intas', 'Micro Labs', 'Ipca', 'Alkem', 'Cipla', 'Sun Pharma', 'Jan Aushadhi',
    ];

    private int $target = 520;

    public function run(): void
    {
        $catalogue = $this->catalogue();

        $rows = [];
        $seen = [];
        $molecules = [];
        $molSeen = [];

        // 1) Branded SKUs
        foreach ($catalogue as $cat) {
            foreach ($cat['items'] as $it) {
                $unit = $it['unit'] ?? $cat['unit'];
                $cost = $it['cost'] ?? $cat['cost'];
                foreach ($it['strengths'] as $s) {
                    $hasNum = (bool) preg_match('/[0-9]/', $s);
                    $name = $hasNum ? $it['brand'] . ' ' . $s : $it['brand'];
                    $strength = $hasNum ? $s : null;
                    if (isset($seen[$name])) continue;
                    $seen[$name] = true;
                    $rows[] = $this->mk($name, $it['generic'], $it['manufacturer'], $unit, $strength, $cost, $cat['hsn']);

                    $key = $it['generic'] . '|' . $s . '|' . $unit . '|' . $cat['name'];
                    if (!isset($molSeen[$key])) {
                        $molSeen[$key] = true;
                        $molecules[] = ['generic' => $it['generic'], 'strength' => $strength, 'unit' => $unit, 'cost' => $cost, 'hsn' => $cat['hsn']];
                    }
                }
            }
        }

        // 2) Generic SKUs (molecule + maker) — molecule-minor, maker-major for even spread
        $mi = 0;
        $guard = count($molecules) * count($this->makers);
        while (count($rows) < $this->target && $mi < $guard) {
            $mol = $molecules[$mi % count($molecules)];
            $mk = $this->makers[intdiv($mi, count($molecules)) % count($this->makers)];
            $mi++;
            $base = $mol['strength'] ? $mol['generic'] . ' ' . $mol['strength'] : $mol['generic'];
            $name = $base . ' (' . $mk . ')';
            if (isset($seen[$name])) continue;
            $seen[$name] = true;
            $rows[] = $this->mk($name, $mol['generic'], $mk, $mol['unit'], $mol['strength'], $mol['cost'], $mol['hsn']);
        }

        // 3) Idempotent insert — skip existing names, chunk 50
        $have = array_flip(DB::table('medicines')->pluck('name')->all());
        $toInsert = array_values(array_filter($rows, fn ($r) => !isset($have[$r['name']])));

        foreach (array_chunk($toInsert, 50) as $chunk) {
            DB::table('medicines')->insertOrIgnore($chunk);
        }

        $this->command->info('Generated ' . count($rows) . ', inserted ' . count($toInsert)
            . ' new. medicines total = ' . DB::table('medicines')->count());
    }

    private function mk($name, $generic, $mfr, $unit, $strength, $cost, $hsn): array
    {
        return [
            'name'          => $name,
            'generic_name'  => $generic,
            'manufacturer'  => $mfr,
            'unit'          => $unit,
            'strength'      => $strength,
            'quantity'      => 100,
            'reorder_level' => 20,
            'expiry_date'   => '2027-12-31',
            'hsn_code'      => $hsn,
            'cost_price'    => $cost,
            'sell_price'    => round($cost * 1.8, 2),
            'status'        => 'active',
            'created_at'    => now(),
            'updated_at'    => now(),
        ];
    }

    private function catalogue(): array
    {
        $hsn = '30049099';
        return [
            ['name' => 'NSAIDs', 'unit' => 'tablet', 'cost' => 3, 'hsn' => $hsn, 'items' => [
                ['brand' => 'Voveran', 'generic' => 'Diclofenac', 'manufacturer' => 'Novartis', 'strengths' => ['50mg', '75mg']],
                ['brand' => 'Dynapar', 'generic' => 'Diclofenac', 'manufacturer' => 'Troikaa', 'strengths' => ['50mg']],
                ['brand' => 'Brufen', 'generic' => 'Ibuprofen', 'manufacturer' => 'Abbott', 'strengths' => ['200mg', '400mg', '600mg']],
                ['brand' => 'Naprosyn', 'generic' => 'Naproxen', 'manufacturer' => 'Cipla', 'strengths' => ['250mg', '500mg']],
                ['brand' => 'Zerodol', 'generic' => 'Aceclofenac', 'manufacturer' => 'Ipca', 'strengths' => ['100mg', '200mg']],
                ['brand' => 'Hifenac', 'generic' => 'Aceclofenac', 'manufacturer' => 'Intas', 'strengths' => ['100mg']],
                ['brand' => 'Aceclo', 'generic' => 'Aceclofenac', 'manufacturer' => 'Aristo', 'strengths' => ['100mg']],
                ['brand' => 'Etoshine', 'generic' => 'Etoricoxib', 'manufacturer' => 'Sun Pharma', 'strengths' => ['60mg', '90mg', '120mg']],
                ['brand' => 'Nucoxia', 'generic' => 'Etoricoxib', 'manufacturer' => 'Zydus', 'strengths' => ['60mg', '90mg']],
                ['brand' => 'Zerodol-P', 'generic' => 'Aceclofenac + Paracetamol', 'manufacturer' => 'Ipca', 'strengths' => ['100mg']],
                ['brand' => 'Flexon', 'generic' => 'Ibuprofen + Paracetamol', 'manufacturer' => 'Aristo', 'strengths' => ['400mg']],
                ['brand' => 'Lornagesic', 'generic' => 'Lornoxicam', 'manufacturer' => 'Sun Pharma', 'strengths' => ['4mg', '8mg']],
            ]],
            ['name' => 'Analgesics', 'unit' => 'tablet', 'cost' => 2, 'hsn' => $hsn, 'items' => [
                ['brand' => 'Dolo', 'generic' => 'Paracetamol', 'manufacturer' => 'Micro Labs', 'strengths' => ['500mg', '650mg']],
                ['brand' => 'Calpol', 'generic' => 'Paracetamol', 'manufacturer' => 'GSK', 'strengths' => ['500mg', '650mg']],
                ['brand' => 'Crocin', 'generic' => 'Paracetamol', 'manufacturer' => 'GSK', 'strengths' => ['500mg', '650mg']],
                ['brand' => 'Ultracet', 'generic' => 'Tramadol + Paracetamol', 'manufacturer' => 'Janssen', 'strengths' => ['325mg']],
                ['brand' => 'Tramazac', 'generic' => 'Tramadol', 'manufacturer' => 'Zydus', 'strengths' => ['50mg', '100mg']],
                ['brand' => 'Domadol', 'generic' => 'Tramadol', 'manufacturer' => 'Zydus', 'strengths' => ['50mg']],
                ['brand' => 'Tapal', 'generic' => 'Tapentadol', 'manufacturer' => 'Sun Pharma', 'strengths' => ['50mg', '100mg']],
                ['brand' => 'Aspadol', 'generic' => 'Tapentadol', 'manufacturer' => 'Signature', 'strengths' => ['50mg', '100mg']],
                ['brand' => 'Combiflam', 'generic' => 'Ibuprofen + Paracetamol', 'manufacturer' => 'Sanofi', 'strengths' => ['400mg']],
            ]],
            ['name' => 'Muscle Relaxants', 'unit' => 'tablet', 'cost' => 5, 'hsn' => $hsn, 'items' => [
                ['brand' => 'Myoril', 'generic' => 'Thiocolchicoside', 'manufacturer' => 'Sanofi', 'strengths' => ['4mg', '8mg']],
                ['brand' => 'Fixonil', 'generic' => 'Thiocolchicoside', 'manufacturer' => 'Cipla', 'strengths' => ['4mg']],
                ['brand' => 'Flexura-D', 'generic' => 'Thiocolchicoside + Aceclofenac', 'manufacturer' => 'Sun Pharma', 'strengths' => ['4mg']],
                ['brand' => 'Mahaflex', 'generic' => 'Chlorzoxazone + Paracetamol', 'manufacturer' => 'Mankind', 'strengths' => ['250mg']],
                ['brand' => 'Chlorzox', 'generic' => 'Chlorzoxazone', 'manufacturer' => 'Khandelwal', 'strengths' => ['250mg', '500mg']],
                ['brand' => 'Flexabenz', 'generic' => 'Cyclobenzaprine', 'manufacturer' => 'Sun Pharma', 'strengths' => ['5mg', '10mg']],
                ['brand' => 'Tizan', 'generic' => 'Tizanidine', 'manufacturer' => 'Sun Pharma', 'strengths' => ['2mg', '4mg']],
                ['brand' => 'Sirdalud', 'generic' => 'Tizanidine', 'manufacturer' => 'Novartis', 'strengths' => ['2mg']],
            ]],
            ['name' => 'Calcium & Bone', 'unit' => 'tablet', 'cost' => 4, 'hsn' => $hsn, 'items' => [
                ['brand' => 'Shelcal', 'generic' => 'Calcium + Vitamin D3', 'manufacturer' => 'Torrent', 'strengths' => ['250mg', '500mg']],
                ['brand' => 'Calcimax', 'generic' => 'Calcium + Vitamin D3', 'manufacturer' => 'Meyer', 'strengths' => ['500mg']],
                ['brand' => 'Gemcal', 'generic' => 'Calcitriol + Calcium', 'manufacturer' => 'Sun Pharma', 'strengths' => ['0.25mcg']],
                ['brand' => 'Rocaltrol', 'generic' => 'Calcitriol', 'manufacturer' => 'Roche', 'strengths' => ['0.25mcg']],
                ['brand' => 'Osteofos', 'generic' => 'Alendronate', 'manufacturer' => 'Cipla', 'strengths' => ['70mg']],
                ['brand' => 'Ganderonate', 'generic' => 'Alendronate', 'manufacturer' => 'Wockhardt', 'strengths' => ['70mg']],
                ['brand' => 'Zoldonat', 'generic' => 'Zoledronic Acid', 'manufacturer' => 'Natco', 'unit' => 'injection', 'cost' => 350, 'strengths' => ['4mg', '5mg']],
                ['brand' => 'Bandrone', 'generic' => 'Ibandronate', 'manufacturer' => 'Emcure', 'strengths' => ['150mg']],
            ]],
            ['name' => 'Vitamins', 'unit' => 'tablet', 'cost' => 3, 'hsn' => $hsn, 'items' => [
                ['brand' => 'Methycobal', 'generic' => 'Mecobalamin', 'manufacturer' => 'Eisai', 'strengths' => ['500mcg']],
                ['brand' => 'Nurokind', 'generic' => 'Mecobalamin', 'manufacturer' => 'Mankind', 'strengths' => ['500mcg', '1500mcg']],
                ['brand' => 'Nervijen', 'generic' => 'Vitamin B-Complex + B12', 'manufacturer' => 'Jenburkt', 'strengths' => ['Cap']],
                ['brand' => 'Uprise-D3', 'generic' => 'Cholecalciferol (Vitamin D3)', 'manufacturer' => 'Alkem', 'unit' => 'sachet', 'strengths' => ['60000IU']],
                ['brand' => 'Calcirol', 'generic' => 'Cholecalciferol (Vitamin D3)', 'manufacturer' => 'Cadila', 'unit' => 'sachet', 'strengths' => ['60000IU']],
                ['brand' => 'Meganeuron-OD', 'generic' => 'Alpha Lipoic Acid + Mecobalamin', 'manufacturer' => 'Sun Pharma', 'unit' => 'capsule', 'strengths' => ['Cap']],
                ['brand' => 'Nurokind-LC', 'generic' => 'Alpha Lipoic Acid + B-Complex', 'manufacturer' => 'Mankind', 'unit' => 'capsule', 'strengths' => ['Cap']],
                ['brand' => 'Alfa-D3', 'generic' => 'Alfacalcidol', 'manufacturer' => 'Panacea', 'strengths' => ['0.25mcg', '0.5mcg']],
            ]],
            ['name' => 'Steroids', 'unit' => 'tablet', 'cost' => 6, 'hsn' => $hsn, 'items' => [
                ['brand' => 'Medrol', 'generic' => 'Methylprednisolone', 'manufacturer' => 'Pfizer', 'strengths' => ['4mg', '8mg', '16mg']],
                ['brand' => 'Solu-Medrol', 'generic' => 'Methylprednisolone', 'manufacturer' => 'Pfizer', 'unit' => 'injection', 'cost' => 120, 'strengths' => ['40mg', '125mg']],
                ['brand' => 'Wysolone', 'generic' => 'Prednisolone', 'manufacturer' => 'Pfizer', 'strengths' => ['5mg', '10mg', '20mg']],
                ['brand' => 'Omnacortil', 'generic' => 'Prednisolone', 'manufacturer' => 'Macleods', 'strengths' => ['5mg', '10mg']],
                ['brand' => 'Dexona', 'generic' => 'Dexamethasone', 'manufacturer' => 'Zydus', 'strengths' => ['0.5mg']],
                ['brand' => 'Decdan', 'generic' => 'Dexamethasone', 'manufacturer' => 'Merck', 'strengths' => ['0.5mg']],
                ['brand' => 'Dexona Inj', 'generic' => 'Dexamethasone', 'manufacturer' => 'Zydus', 'unit' => 'injection', 'cost' => 15, 'strengths' => ['4mg/ml']],
            ]],
            ['name' => 'Topicals', 'unit' => 'cream', 'cost' => 45, 'hsn' => $hsn, 'items' => [
                ['brand' => 'Volini Gel', 'generic' => 'Diclofenac', 'manufacturer' => 'Sun Pharma', 'strengths' => ['1%']],
                ['brand' => 'Voveran Emulgel', 'generic' => 'Diclofenac', 'manufacturer' => 'Novartis', 'strengths' => ['1%']],
                ['brand' => 'Omnigel', 'generic' => 'Diclofenac', 'manufacturer' => 'Cipla', 'strengths' => ['1%']],
                ['brand' => 'Moov', 'generic' => 'Diclofenac + Methyl Salicylate', 'manufacturer' => 'Reckitt', 'strengths' => ['Cream']],
                ['brand' => 'Dynapar QPS', 'generic' => 'Diclofenac', 'manufacturer' => 'Troikaa', 'strengths' => ['Spray']],
                ['brand' => 'Thrombophob', 'generic' => 'Heparin + Benzyl Nicotinate', 'manufacturer' => 'Intas', 'strengths' => ['Gel']],
                ['brand' => 'Relispray', 'generic' => 'Methyl Salicylate + Menthol', 'manufacturer' => 'Dr Morepen', 'strengths' => ['Spray']],
            ]],
            ['name' => 'Antibiotics', 'unit' => 'tablet', 'cost' => 8, 'hsn' => $hsn, 'items' => [
                ['brand' => 'Augmentin', 'generic' => 'Amoxicillin + Clavulanic Acid', 'manufacturer' => 'GSK', 'strengths' => ['375mg', '625mg']],
                ['brand' => 'Mox', 'generic' => 'Amoxicillin', 'manufacturer' => 'Sun Pharma', 'unit' => 'capsule', 'strengths' => ['250mg', '500mg']],
                ['brand' => 'Azithral', 'generic' => 'Azithromycin', 'manufacturer' => 'Alembic', 'strengths' => ['250mg', '500mg']],
                ['brand' => 'Azee', 'generic' => 'Azithromycin', 'manufacturer' => 'Cipla', 'strengths' => ['250mg', '500mg']],
                ['brand' => 'Ceftum', 'generic' => 'Cefuroxime', 'manufacturer' => 'GSK', 'strengths' => ['250mg', '500mg']],
                ['brand' => 'Altacef', 'generic' => 'Cefuroxime', 'manufacturer' => 'Glenmark', 'strengths' => ['500mg']],
                ['brand' => 'Taxim-O', 'generic' => 'Cefixime', 'manufacturer' => 'Alkem', 'strengths' => ['200mg']],
            ]],
            ['name' => 'Antacids', 'unit' => 'tablet', 'cost' => 3, 'hsn' => $hsn, 'items' => [
                ['brand' => 'Pan', 'generic' => 'Pantoprazole', 'manufacturer' => 'Alkem', 'strengths' => ['20mg', '40mg']],
                ['brand' => 'Pan-D', 'generic' => 'Pantoprazole + Domperidone', 'manufacturer' => 'Alkem', 'unit' => 'capsule', 'strengths' => ['40mg']],
                ['brand' => 'Pantop', 'generic' => 'Pantoprazole', 'manufacturer' => 'Aristo', 'strengths' => ['40mg']],
                ['brand' => 'Omez', 'generic' => 'Omeprazole', 'manufacturer' => 'Dr Reddys', 'unit' => 'capsule', 'strengths' => ['20mg', '40mg']],
                ['brand' => 'Razo', 'generic' => 'Rabeprazole', 'manufacturer' => 'Dr Reddys', 'strengths' => ['20mg']],
                ['brand' => 'Razo-D', 'generic' => 'Rabeprazole + Domperidone', 'manufacturer' => 'Dr Reddys', 'unit' => 'capsule', 'strengths' => ['20mg']],
                ['brand' => 'Nexpro', 'generic' => 'Esomeprazole', 'manufacturer' => 'Torrent', 'strengths' => ['20mg', '40mg']],
            ]],
            ['name' => 'Neuropathy', 'unit' => 'capsule', 'cost' => 6, 'hsn' => $hsn, 'items' => [
                ['brand' => 'Pregabid', 'generic' => 'Pregabalin', 'manufacturer' => 'Intas', 'strengths' => ['75mg', '150mg']],
                ['brand' => 'Pregalin', 'generic' => 'Pregabalin', 'manufacturer' => 'Sun Pharma', 'strengths' => ['75mg']],
                ['brand' => 'Nurokind-G', 'generic' => 'Pregabalin + Mecobalamin', 'manufacturer' => 'Mankind', 'strengths' => ['75mg']],
                ['brand' => 'Gabapin', 'generic' => 'Gabapentin', 'manufacturer' => 'Intas', 'strengths' => ['100mg', '300mg']],
                ['brand' => 'Gabantin', 'generic' => 'Gabapentin', 'manufacturer' => 'Sun Pharma', 'strengths' => ['100mg', '300mg']],
                ['brand' => 'Duzela', 'generic' => 'Duloxetine', 'manufacturer' => 'Sun Pharma', 'strengths' => ['20mg', '30mg']],
                ['brand' => 'Dulane', 'generic' => 'Duloxetine', 'manufacturer' => 'Sun Pharma', 'strengths' => ['20mg', '30mg']],
            ]],
            ['name' => 'Injections', 'unit' => 'injection', 'cost' => 120, 'hsn' => $hsn, 'items' => [
                ['brand' => 'Synvisc', 'generic' => 'Hyaluronic Acid', 'manufacturer' => 'Sanofi', 'cost' => 8500, 'strengths' => ['1 vial']],
                ['brand' => 'Osteonil', 'generic' => 'Hyaluronic Acid', 'manufacturer' => 'TRB Chemedica', 'cost' => 4500, 'strengths' => ['1 vial']],
                ['brand' => 'Hyalgan', 'generic' => 'Hyaluronic Acid', 'manufacturer' => 'Fidia', 'cost' => 3500, 'strengths' => ['1 vial']],
                ['brand' => 'PRP Kit', 'generic' => 'Platelet Rich Plasma Kit', 'manufacturer' => 'Generic', 'cost' => 1200, 'strengths' => ['1 kit']],
                ['brand' => 'Voveran Inj', 'generic' => 'Diclofenac', 'manufacturer' => 'Novartis', 'cost' => 12, 'strengths' => ['75mg/3ml']],
                ['brand' => 'Zoledronic Inj', 'generic' => 'Zoledronic Acid', 'manufacturer' => 'Natco', 'cost' => 1500, 'strengths' => ['4mg']],
                ['brand' => 'Tramadol Inj', 'generic' => 'Tramadol', 'manufacturer' => 'Zydus', 'cost' => 10, 'strengths' => ['50mg/ml', '100mg/2ml']],
            ]],
        ];
    }
}
