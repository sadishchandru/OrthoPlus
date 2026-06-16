<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SoapTemplate;
use Illuminate\Http\Request;

class SoapTemplateController extends Controller
{
    public function index()
    {
        return response()->json(
            SoapTemplate::where('is_active', true)->orderBy('name')->get()
        );
    }

    public function store(Request $request)
    {
        $tpl = SoapTemplate::create($this->validateData($request));
        return response()->json($tpl, 201);
    }

    public function update(Request $request, SoapTemplate $soapTemplate)
    {
        $soapTemplate->update($this->validateData($request));
        return response()->json($soapTemplate);
    }

    public function destroy(SoapTemplate $soapTemplate)
    {
        $soapTemplate->delete();
        return response()->json(['message' => 'Deleted']);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name'       => 'required|string|max:255',
            'subjective' => 'nullable|string',
            'objective'  => 'nullable|string',
            'assessment' => 'nullable|string',
            'plan'       => 'nullable|string',
            'is_active'  => 'boolean',
        ]);
    }
}
