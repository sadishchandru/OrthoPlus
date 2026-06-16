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
});

// SPA catch-all — Vue Router handles everything else
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
