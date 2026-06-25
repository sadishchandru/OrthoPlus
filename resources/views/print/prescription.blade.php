@php
require_once resource_path('views/print/_helpers.php');
$t    = printLang(request('lang', 'en'));
$rx   = \App\Models\Prescription::with('patient')->findOrFail($id);
$pat  = $rx->patient;
$items = $rx->items ?? $rx->medications ?? [];
$medicineIds = collect($items)->pluck('medicine_id')->filter()->toArray();
$medMap = \App\Models\Medicine::whereIn('id', $medicineIds)->get()->keyBy('id');
$rec = $rx->clinical_record_id ? \App\Models\ClinicalRecord::find($rx->clinical_record_id) : null;
@endphp
@extends('print.layout')
@section('title', $t['prescription'] . ' — ' . $pat->name)

@section('head')
<style>
    .rx-symbol { font-size: 48px; font-weight: 900; color: #2E7D32; line-height: 1; float: left; margin-right: 12px; }
</style>
@endsection

@section('content')
<div style="display:flex; align-items:flex-start; margin-bottom:14px;">
    <div class="rx-symbol">℞</div>
    <div>
        <div style="font-size:14px; font-weight:700; color:#2E7D32;">{{ $t['prescription'] }}</div>
        <div style="font-size:12px; color:#555; margin-top:2px;">
            <strong>{{ $t['patient_name'] }}:</strong> {{ $pat->name }} ({{ $pat->op_number }})
            &nbsp;&nbsp; <strong>{{ $t['date'] }}:</strong> {{ \Carbon\Carbon::parse($rx->created_at)->format('d M Y') }}
        </div>
    </div>
</div>

@if($rec && $rec->pain_description)
<div style="background:#F1F8F2; border-radius:6px; padding:8px 10px; margin-bottom:12px; font-size:12px;">
    <strong style="color:#2E7D32;">Pain:</strong> {{ $rec->pain_description }}
</div>
@endif

<table style="margin-top:8px;">
    <thead>
        <tr>
            <th>#</th>
            <th>{{ $t['medicine'] }}</th>
            <th>{{ $t['dose'] }}</th>
            <th>{{ $t['freq'] }}</th>
            <th>{{ $t['duration'] }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $i => $item)
        @php $med = $medMap[$item['medicine_id'] ?? 0] ?? null; @endphp
        <tr>
            <td style="text-align:center;">{{ $i + 1 }}</td>
            <td>
                <strong>{{ $med?->name ?? ($item['name'] ?? '—') }}</strong>
                @if($med?->generic_name)
                <div style="font-size:11px; color:#9ca3af;">{{ $med->generic_name }}</div>
                @endif
            </td>
            <td>{{ $item['dose'] ?? '—' }}</td>
            <td>{{ $item['freq'] ?? '—' }}</td>
            <td>{{ $item['duration'] ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

@if(!empty($rx->notes))
<div class="section-title">{{ $t['notes'] }}</div>
<p style="color:#374151; line-height:1.6; font-size:12px;">{{ $rx->notes }}</p>
@endif

<div class="sig-block">
    <div class="sig-line">
        <hr>
        <div style="font-size:11px; color:#555;">{{ $t['doctor_signature'] }}</div>
    </div>
</div>
@endsection
