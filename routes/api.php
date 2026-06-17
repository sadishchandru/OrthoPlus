<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\ClinicalRecordController;
use App\Http\Controllers\Api\TreatmentController;
use App\Http\Controllers\Api\TreatmentCatalogController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ExerciseController;
use App\Http\Controllers\Api\TherapistController;
use App\Http\Controllers\Api\MedicineController;
use App\Http\Controllers\Api\SoapTemplateController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PharmacyController;

// ---- Auth ----------------------------------------------------------------
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/me', [AuthController::class, 'me']);
Route::post('/auth/logout', [AuthController::class, 'logout']);

// ---- Patients ------------------------------------------------------------
Route::prefix('patients')->group(function () {
    Route::get('/', [PatientController::class, 'index']);
    Route::post('/', [PatientController::class, 'store']);
    Route::get('/search', [PatientController::class, 'search']);
    Route::get('/{patient}', [PatientController::class, 'show']);
    Route::get('/{patient}/visits', [PatientController::class, 'visits']);
});

Route::prefix('appointments')->group(function () {
    Route::get('/calendar', [AppointmentController::class, 'calendar']);
    Route::post('/', [AppointmentController::class, 'store']);
    Route::put('/{appointment}', [AppointmentController::class, 'update']);
});

Route::prefix('clinical-records')->group(function () {
    Route::post('/', [ClinicalRecordController::class, 'store']);
    Route::get('/{clinicalRecord}', [ClinicalRecordController::class, 'show']);
    Route::put('/{clinicalRecord}', [ClinicalRecordController::class, 'update']);
});

Route::prefix('treatments')->group(function () {
    Route::get('/', [TreatmentController::class, 'index']);
    Route::get('/catalog', [TreatmentCatalogController::class, 'active']);
    Route::post('/assign', [TreatmentController::class, 'assign']);
    Route::put('/{treatment}/complete', [TreatmentController::class, 'complete']);
});

Route::prefix('exercises')->group(function () {
    Route::get('/', [ExerciseController::class, 'index']);
    Route::get('/categories', [ExerciseController::class, 'categories']);
    Route::post('/prescribe', [ExerciseController::class, 'prescribe']);
});

Route::prefix('prescriptions')->group(function () {
    Route::get('/', [PrescriptionController::class, 'index']);
    Route::post('/', [PrescriptionController::class, 'store']);
    Route::get('/{prescription}/print', [PrescriptionController::class, 'print']);
});

Route::get('/medicines/search', [MedicineController::class, 'search']);

Route::prefix('invoices')->group(function () {
    Route::post('/', [InvoiceController::class, 'store']);
    Route::put('/{invoice}', [InvoiceController::class, 'update']);
    Route::get('/{invoice}/print', [InvoiceController::class, 'print']);
});

Route::prefix('inventory')->group(function () {
    Route::get('/', [InventoryController::class, 'index']);
    Route::post('/adjust', [InventoryController::class, 'adjust']);
    Route::get('/logs', [InventoryController::class, 'logs']);
});

Route::prefix('reports')->group(function () {
    Route::get('/dashboard', [ReportController::class, 'dashboard']);
    Route::get('/therapist', [ReportController::class, 'therapist']);
});

// ---- Simple dropdown lists ----------------------------------------------
Route::get('/therapists', [TherapistController::class, 'active']);       // is_active only
Route::get('/rooms', fn() => \App\Models\Room::where('is_active', true)->get());
Route::get('/packages', fn() => \App\Models\Package::where('is_active', true)->get());
Route::get('/soap-templates', [SoapTemplateController::class, 'index']);

// ---- Pharmacy counter (open like the rest of the app; page is router-guarded) ----
Route::prefix('pharmacy')->group(function () {
    Route::get('/patients/search', [PharmacyController::class, 'searchPatient']);
    Route::get('/patients/{patient}/prescriptions', [PharmacyController::class, 'prescriptions']);
    Route::post('/invoices', [PharmacyController::class, 'store']);
});

// ---- Settings / master data (admin) --------------------------------------
Route::prefix('settings')->middleware('role:root')->group(function () {
    Route::apiResource('treatment-catalog', TreatmentCatalogController::class)->except(['show']);
    Route::apiResource('exercises', ExerciseController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('medicines', MedicineController::class)->except(['show']);
    Route::apiResource('therapists', TherapistController::class)->except(['show']);
    Route::apiResource('soap-templates', SoapTemplateController::class)->except(['show']);

    Route::get('users/roles', [UserController::class, 'roles']);
    Route::apiResource('users', UserController::class)->except(['show']);
});
