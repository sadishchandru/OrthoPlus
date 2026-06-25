<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PatientFileController extends Controller
{
    /** List a patient's files (lightweight; lazy-loaded by the gallery). */
    public function index(Request $request, Patient $patient)
    {
        $files = PatientFile::query()
            ->where('patient_id', $patient->id)
            ->when($request->filled('module'), fn($q) => $q->where('module', $request->module))
            ->orderByDesc('id')
            ->get(['id', 'patient_id', 'module', 'original_name', 'path', 'mime', 'category', 'size', 'created_at']);

        return response()->json($files);
    }

    /** Multi-file upload. Existing files are preserved (append-only). */
    public function store(Request $request, Patient $patient)
    {
        // Validate by extension (mimes: sniffs docx/xlsx as zip and wrongly rejects them).
        $request->validate([
            'files'   => 'required|array|min:1',
            'files.*' => 'file|max:25600', // 25 MB each
            'module'  => 'nullable|string|max:50',
        ]);

        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'bmp', 'pdf',
                    'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'rtf'];

        foreach ($request->file('files') as $file) {
            $ext = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, $allowed, true)) {
                return response()->json([
                    'message' => "Unsupported file type: .{$ext}",
                    'errors'  => ['files' => ["Unsupported file type: .{$ext}. Allowed: " . implode(', ', $allowed)]],
                ], 422);
            }
        }

        $saved = [];
        foreach ($request->file('files') as $file) {
            $path = $file->store("patient_files/{$patient->id}", 'public');
            $saved[] = PatientFile::create([
                'patient_id'    => $patient->id,
                'module'        => $request->input('module'),
                'original_name' => $file->getClientOriginalName(),
                'path'          => $path,
                'mime'          => $file->getClientMimeType(),
                'category'      => $this->categorize($file->getClientMimeType(), $file->getClientOriginalExtension()),
                'size'          => $file->getSize(),
                'uploaded_by'   => auth()->id(),
            ]);
        }

        return response()->json($saved, 201);
    }

    /** Delete a single file (selected-file delete). */
    public function destroy(PatientFile $file)
    {
        Storage::disk('public')->delete($file->path);
        $file->delete();
        return response()->json(['deleted' => true]);
    }

    private function categorize(?string $mime, ?string $ext): string
    {
        $mime = (string) $mime;
        if (str_starts_with($mime, 'image/')) return 'image';
        if ($mime === 'application/pdf' || strtolower((string) $ext) === 'pdf') return 'pdf';
        if (str_contains($mime, 'word') || in_array(strtolower((string) $ext), ['doc', 'docx'], true)) return 'doc';
        return 'other';
    }
}
