<?php

use Illuminate\Support\Facades\Route;

// Print routes (Blade)
Route::prefix('print')->group(function () {
    Route::get('/patient/{id}', fn($id) => view('print.patient_record', ['id' => $id]));
    Route::get('/soap/{id}', fn($id) => view('print.soap_note', ['id' => $id]));
    Route::get('/exercises/{id}', fn($id) => view('print.exercise_sheet', ['id' => $id]));
    Route::get('/prescription/{id}', fn($id) => view('print.prescription', ['id' => $id]));
    Route::get('/invoice/{id}', fn($id) => view('print.invoice', ['id' => $id]));
    Route::get('/consent', fn() => view('print.consent_form'));

    // ---- Hospital (in-patient) print views ----
    Route::get('/admission-card/{id}',    fn($id) => view('print.admission-card',    ['id' => $id]));
    Route::get('/ip-bill/{id}',           fn($id) => view('print.ip-bill',           ['id' => $id]));
    Route::get('/discharge/{id}',         fn($id) => view('print.discharge-summary', ['id' => $id]));
    Route::get('/surgery/{id}',           fn($id) => view('print.surgery-note',      ['id' => $id]));
    Route::get('/opd-token/{id}',         fn($id) => view('print.opd-token',         ['id' => $id]));
    Route::get('/pre-op/{id}',            fn($id) => view('print.pre-op-plan',       ['id' => $id]));
    Route::get('/implant/{id}',           fn($id) => view('print.implant-sticker',   ['id' => $id]));
});

// SPA catch-all — Vue Router handles everything else
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
