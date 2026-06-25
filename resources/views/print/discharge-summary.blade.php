@php
require_once resource_path('views/print/_helpers.php');
$t   = printLang(request('lang', 'en'));
$ds  = \App\Models\DischargeSummary::with(['patient', 'admission.ward', 'admission.bed'])->findOrFail($id);
$pat = $ds->patient;
$adm = $ds->admission;
@endphp
@extends('print.layout')
@section('title', $t['discharge_summary'] . ' — ' . ($pat->name ?? ''))

@section('content')
<h2 style="font-size:18px; font-weight:700; color:#2E7D32; margin-bottom:8px;">{{ $t['discharge_summary'] }}</h2>

<div style="background:#F1F8F2; border-radius:8px; padding:10px 12px; margin-bottom:14px; font-size:12px;">
    <div class="row"><span class="label">{{ $t['patient_name'] }}:</span><span class="value">{{ $pat->name ?? '—' }}</span></div>
    <div class="row"><span class="label">{{ $t['op_number'] }} / {{ $t['ip_number'] }}:</span><span class="value">{{ $pat->op_number ?? '—' }} / {{ $adm->ip_number ?? '—' }}</span></div>
    <div class="row"><span class="label">{{ $t['ward'] }} / {{ $t['bed'] }}:</span><span class="value">{{ $adm->ward->name ?? '—' }} / {{ $adm->bed->bed_number ?? '—' }}</span></div>
    <div class="row"><span class="label">{{ $t['admission_date'] }}:</span><span class="value">{{ $adm && $adm->admission_date ? \Carbon\Carbon::parse($adm->admission_date)->format('d M Y') : '—' }} &nbsp;→&nbsp; {{ $adm && $adm->discharge_date ? \Carbon\Carbon::parse($adm->discharge_date)->format('d M Y') : '—' }}</span></div>
    <div class="row"><span class="label">{{ $t['condition'] }}:</span><span class="value" style="text-transform:capitalize;">{{ $ds->discharge_condition }}</span></div>
</div>

<div class="section-title">{{ $t['final_diagnosis'] }}</div>
<p style="font-size:12px;">{{ $ds->diagnosis_final ?: '—' }}</p>

<div class="section-title">{{ $t['procedures'] }}</div>
@if(!empty($ds->procedures_done))
<ul style="font-size:12px; margin-left:18px;">@foreach($ds->procedures_done as $p)<li>{{ is_array($p) ? implode(' — ', $p) : $p }}</li>@endforeach</ul>
@else<p style="font-size:12px;">—</p>@endif

<div class="section-title">{{ $t['discharge_meds'] }}</div>
@if(!empty($ds->discharge_medications))
<table><thead><tr><th>{{ $t['medicine'] }}</th><th>{{ $t['dose'] }}</th><th>{{ $t['freq'] }}</th><th>{{ $t['duration'] }}</th></tr></thead><tbody>
@foreach($ds->discharge_medications as $m)
<tr><td>{{ is_array($m) ? ($m['name'] ?? '—') : $m }}</td><td>{{ $m['dose'] ?? '' }}</td><td>{{ $m['freq'] ?? ($m['frequency'] ?? '') }}</td><td>{{ $m['duration'] ?? '' }}</td></tr>
@endforeach
</tbody></table>
@else<p style="font-size:12px;">—</p>@endif

<div class="section-title">{{ $t['instructions'] }}</div>
<p style="font-size:12px;">{{ $ds->discharge_instructions ?: '—' }}</p>

<div class="row" style="margin-top:10px; font-size:12px;"><span class="label">{{ $t['follow_up'] }}:</span><span class="value">{{ $ds->follow_up_date ? \Carbon\Carbon::parse($ds->follow_up_date)->format('d M Y') : '—' }}</span></div>

<div class="sig-block"><div class="sig-line"><hr><div style="font-size:11px; color:#555;">{{ $t['doctor_signature'] }}</div></div></div>
@endsection
