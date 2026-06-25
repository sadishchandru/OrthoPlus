@php
require_once resource_path('views/print/_helpers.php');
$t   = printLang(request('lang', 'en'));
$pat = \App\Models\Patient::findOrFail($id);
$records = \App\Models\ClinicalRecord::where('patient_id', $id)
    ->orderByDesc('created_at')->limit(5)->get();
@endphp
@extends('print.layout')
@section('title', $t['patient_record'] . ' — ' . $pat->name)

@section('content')
<h2 style="font-size:16px; font-weight:700; color:#2E7D32; margin-bottom:12px;">{{ $t['patient_record'] }}</h2>

<div class="section-title">{{ $t['patient_name'] }}</div>
<div class="row"><span class="label">{{ $t['patient_name'] }}:</span> <span class="value">{{ $pat->name }}</span></div>
<div class="row"><span class="label">{{ $t['op_number'] }}:</span> <span class="value"><strong>{{ $pat->op_number }}</strong></span></div>
<div class="row"><span class="label">{{ $t['phone'] }}:</span> <span class="value">{{ $pat->phone }}</span></div>
<div class="row"><span class="label">{{ $t['dob'] }}:</span> <span class="value">{{ $pat->dob ? \Carbon\Carbon::parse($pat->dob)->format('d M Y') : '—' }}</span></div>
<div class="row"><span class="label">{{ $t['gender'] }}:</span> <span class="value">{{ ucfirst($pat->gender ?? '—') }}</span></div>
@if(is_array($pat->address) && !empty($pat->address))
<div class="row">
    <span class="label">{{ $t['address'] }}:</span>
    <span class="value">
        {{ implode(', ', array_filter([$pat->address['line1'] ?? null, $pat->address['city'] ?? null, $pat->address['state'] ?? null, $pat->address['pincode'] ?? null])) }}
    </span>
</div>
@endif

@if($records->count())
<div class="section-title">{{ $t['visit_summary'] }}</div>
<table>
    <thead>
        <tr>
            <th>{{ $t['visit_no'] }}</th>
            <th>{{ $t['date'] }}</th>
            <th>{{ $t['vas_score'] }}</th>
            <th>{{ $t['assessment'] }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $r)
        <tr>
            <td><span class="badge badge-blue">#{{ $r->visit_no }}</span></td>
            <td>{{ \Carbon\Carbon::parse($r->created_at)->format('d M Y') }}</td>
            <td>{{ $r->vas_score ?? '—' }}/10</td>
            <td>{{ $r->soap_notes['assessment'] ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="sig-block">
    <div class="sig-line">
        <hr>
        <div style="font-size:11px; color:#555;">{{ $t['doctor_signature'] }}</div>
    </div>
</div>
@endsection
