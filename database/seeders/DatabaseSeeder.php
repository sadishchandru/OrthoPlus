<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OrthoSeeder::class,
            TreatmentCatalogSeeder::class,
            ExerciseSeeder::class,
            IndianMedicinesSeeder::class,
            ClinicalTemplateSeeder::class,
        ]);
    }
}