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
use App\Http\Controllers\Api\PatientFileController;
// ---- Hospital upgrade controllers ----------------------------------------
use App\Http\Controllers\Api\WardController;
use App\Http\Controllers\Api\BedController;
use App\Http\Controllers\Api\AdmissionController;
use App\Http\Controllers\Api\OpdController;
use App\Http\Controllers\Api\OpdVisitController;
use App\Http\Controllers\Api\SurgeryController;
use App\Http\Controllers\Api\ImplantController;
use App\Http\Controllers\Api\PreOpPlanController;
use App\Http\Controllers\Api\PreferenceCardController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\ImagingController;
use App\Http\Controllers\Api\ChargeMasterController;
use App\Http\Controllers\Api\IpBillingController;
use App\Http\Controllers\Api\GlobalPeriodController;
use App\Http\Controllers\Api\OpOrderController;
use App\Http\Controllers\Hospital\HospitalReportController;

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
    Route::put('/{patient}', [PatientController::class, 'update']);
    Route::get('/{patient}/visits', [PatientController::class, 'visits']);
    // Patient files (real uploads: image/pdf/doc)
    Route::get('/{patient}/files', [PatientFileController::class, 'index']);
    Route::post('/{patient}/files', [PatientFileController::class, 'store']);
});
Route::delete('/patient-files/{file}', [PatientFileController::class, 'destroy']);

Route::prefix('appointments')->group(function () {
    Route::get('/calendar', [AppointmentController::class, 'calendar']);
    Route::get('/check-availability', [AppointmentController::class, 'checkAvailability']);
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
    Route::get('/{prescription}', [PrescriptionController::class, 'show']);
    Route::put('/{prescription}', [PrescriptionController::class, 'update']);
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

    // Branding / theme / print designer (root only)
    Route::get('branding', [\App\Http\Controllers\Api\SettingController::class, 'show']);
    Route::put('branding', [\App\Http\Controllers\Api\SettingController::class, 'update']);
});

// ==========================================================================
//  HOSPITAL UPGRADE — In-Patient HMS modules (open like the rest of the app;
//  pages are router-guarded on the frontend via page_access keys).
// ==========================================================================

// ---- In-Patients & Beds --------------------------------------------------
Route::get('beds/available', [BedController::class, 'available']);          // before {bed}
Route::apiResource('beds', BedController::class);
Route::get('wards/{ward}/beds', [WardController::class, 'beds']);
Route::apiResource('wards', WardController::class);

Route::post('admissions/{admission}/transfer-bed', [AdmissionController::class, 'transferBed']);
Route::post('admissions/{admission}/discharge', [AdmissionController::class, 'discharge']);
Route::apiResource('admissions', AdmissionController::class);

// ---- OPD Queue -----------------------------------------------------------
Route::get('opd/queue/today', [OpdController::class, 'todayQueue']);
Route::post('opd/queue', [OpdController::class, 'addToQueue']);
Route::put('opd/queue/{opd_queue}/status', [OpdController::class, 'updateStatus']);
Route::apiResource('opd-visits', OpdVisitController::class);

// ---- Surgery & OR --------------------------------------------------------
Route::get('surgeries/schedule', [SurgeryController::class, 'schedule']);   // before {surgery}
Route::apiResource('surgeries', SurgeryController::class);
Route::get('implants/low-stock', [ImplantController::class, 'lowStock']);   // before {implant}
Route::post('implants/{implant}/adjust', [ImplantController::class, 'adjust']);
Route::apiResource('implants', ImplantController::class);
Route::apiResource('pre-op-plans', PreOpPlanController::class);
Route::apiResource('preference-cards', PreferenceCardController::class);

// ---- Staff ---------------------------------------------------------------
Route::get('staff/on-duty', [StaffController::class, 'onDuty']);            // before {staff}
Route::post('staff/{staff}/shift', [StaffController::class, 'assignShift']);
Route::apiResource('staff', StaffController::class);
Route::put('leave-requests/{leave_request}/approve', [LeaveRequestController::class, 'approve']);
Route::apiResource('leave-requests', LeaveRequestController::class);

// ---- Imaging -------------------------------------------------------------
Route::post('imaging-orders/{imaging_order}/upload', [ImagingController::class, 'uploadImages']);
Route::apiResource('imaging-orders', ImagingController::class);
Route::get('patients/{patient}/imaging', [ImagingController::class, 'patientImages']);

// ---- Billing -------------------------------------------------------------
Route::get('charge-master', [ChargeMasterController::class, 'index']);
Route::post('charge-master', [ChargeMasterController::class, 'store']);
Route::delete('charge-master/{charge_master}', [ChargeMasterController::class, 'destroy']);
Route::post('ip-bills', [IpBillingController::class, 'generate']);
Route::post('ip-bills/{ip_bill}/finalize', [IpBillingController::class, 'finalize']);
Route::get('ip-bills/{admission_id}', [IpBillingController::class, 'show']);
Route::get('global-periods/{patient}', [GlobalPeriodController::class, 'index']);
Route::post('global-periods', [GlobalPeriodController::class, 'store']);

// ---- O&P (Orthotic & Prosthetic) -----------------------------------------
Route::post('op-orders/{op_order}/fitting', [OpOrderController::class, 'addFitting']);
Route::apiResource('op-orders', OpOrderController::class);

// ---- Hospital reports ----------------------------------------------------
Route::prefix('reports')->group(function () {
    Route::get('/ip-census', [ReportController::class, 'ipCensus']);
    Route::get('/bed-occupancy', [ReportController::class, 'bedOccupancy']);
    Route::get('/surgery-list', [ReportController::class, 'surgeryList']);
    Route::get('/revenue-ip', [ReportController::class, 'revenueIp']);
    Route::get('/implant-usage', [ReportController::class, 'implantUsage']);
    Route::get('/staff-attendance', [ReportController::class, 'staffAttendance']);
    Route::get('/discharge-summary', [ReportController::class, 'dischargeSummary']);
    Route::get('/global-periods', [ReportController::class, 'globalPeriods']);
});

// ==========================================================================
//  /api/hospital/* — namespaced alias group for the Hospital (HMS) portal.
//  Maps to the existing Api\ controllers (no duplicate logic) + a dedicated
//  hospital dashboard report. Coexists with the flat routes above.
// ==========================================================================
Route::prefix('hospital')->group(function () {
    // Wards & Beds
    Route::get('wards', [WardController::class, 'index']);
    Route::post('wards', [WardController::class, 'store']);
    Route::put('wards/{ward}', [WardController::class, 'update']);
    Route::get('wards/{ward}/beds', [WardController::class, 'beds']);
    Route::get('beds/available', [BedController::class, 'available']);   // before {bed}
    Route::get('beds', [BedController::class, 'index']);
    Route::post('beds', [BedController::class, 'store']);
    Route::put('beds/{bed}', [BedController::class, 'update']);
    Route::patch('beds/{bed}/status', [BedController::class, 'updateStatus']);

    // Admissions
    Route::get('admissions', [AdmissionController::class, 'index']);
    Route::post('admissions', [AdmissionController::class, 'store']);
    Route::get('admissions/{admission}', [AdmissionController::class, 'show']);
    Route::post('admissions/{admission}/transfer-bed', [AdmissionController::class, 'transferBed']);
    Route::post('admissions/{admission}/discharge', [AdmissionController::class, 'discharge']);

    // OPD Queue
    Route::get('opd/queue', [OpdController::class, 'todayQueue']);
    Route::post('opd/queue', [OpdController::class, 'addToQueue']);
    Route::patch('opd/queue/{opd_queue}/status', [OpdController::class, 'updateStatus']);
    Route::get('opd/queue/{opd_queue}/consult', [OpdController::class, 'getConsultation']);
    Route::post('opd/queue/{opd_queue}/consult', [OpdController::class, 'saveConsultation']);

    // Staff
    Route::get('staff/on-duty', [StaffController::class, 'onDuty']);     // before {staff}
    Route::get('staff', [StaffController::class, 'index']);
    Route::post('staff', [StaffController::class, 'store']);
    Route::put('staff/{staff}', [StaffController::class, 'update']);

    // Billing
    Route::get('charge-master', [ChargeMasterController::class, 'index']);
    Route::post('charge-master', [ChargeMasterController::class, 'store']);
    Route::put('charge-master/{charge_master}', [ChargeMasterController::class, 'update']);
    Route::post('ip-bills', [IpBillingController::class, 'generate']);
    Route::post('ip-bills/{ip_bill}/finalize', [IpBillingController::class, 'finalize']);
    Route::get('ip-bills/{admission_id}', [IpBillingController::class, 'show']);

    // Reports
    Route::get('reports/dashboard', [HospitalReportController::class, 'dashboard']);
    Route::get('reports/bed-occupancy', [HospitalReportController::class, 'bedOccupancy']);
    Route::get('reports/census', [HospitalReportController::class, 'census']);
});
