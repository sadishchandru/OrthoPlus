@php
require_once resource_path('views/print/_helpers.php');
$t   = printLang(request('lang', 'en'));
$rec = \App\Models\ClinicalRecord::with('patient')->findOrFail($id);
$pat = $rec->patient;
@endphp
@extends('print.layout')
@section('title', $t['soap_note'] . ' — ' . $pat->name)

@section('content')
<h2 style="font-size:16px; font-weight:700; color:#2E7D32; margin-bottom:8px;">{{ $t['soap_note'] }}</h2>

<div style="display:flex; gap:24px; margin-bottom:10px; font-size:12px;">
    <div><strong>{{ $t['patient_name'] }}:</strong> {{ $pat->name }} ({{ $pat->op_number }})</div>
    <div><strong>{{ $t['visit_no'] }}:</strong> #{{ $rec->visit_no }}</div>
    <div><strong>{{ $t['date'] }}:</strong> {{ \Carbon\Carbon::parse($rec->created_at)->format('d M Y') }}</div>
    @if($rec->vas_score !== null)
    <div><strong>{{ $t['vas_score'] }}:</strong> <span style="color:{{ $rec->vas_score <= 3 ? '#16a34a' : ($rec->vas_score <= 6 ? '#d97706' : '#dc2626') }}; font-weight:700;">{{ $rec->vas_score }}/10</span></div>
    @endif
</div>

@if($rec->soap_notes)
@foreach(['subjective','objective','assessment','plan'] as $key)
@if(!empty($rec->soap_notes[$key]))
<div class="section-title">{{ $t[$key] }}</div>
<p style="white-space:pre-wrap; line-height:1.6; color:#374151;">{{ $rec->soap_notes[$key] }}</p>
@endif
@endforeach
@endif

@if($rec->rom && count($rec->rom))
<div class="section-title">{{ $t['rom'] }}</div>
<table>
    <thead>
        <tr>
            <th>{{ $t['joint'] }}</th>
            <th>{{ $t['movement'] }}</th>
            <th style="text-align:right;">{{ $t['degrees'] }}</th>
            <th style="text-align:right;">{{ $t['normal'] }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rec->rom as $r)
        <tr>
            <td>{{ $r['joint'] ?? '' }}</td>
            <td>{{ $r['movement'] ?? '' }}</td>
            <td style="text-align:right;">{{ $r['degrees'] ?? '—' }}°</td>
            <td style="text-align:right; color:#9ca3af;">{{ $r['normal'] ?? '—' }}°</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

@if($rec->ortho_tests && count($rec->ortho_tests))
<div class="section-title">{{ $t['ortho_tests'] }}</div>
<table>
    <thead>
        <tr>
            <th>{{ $t['test'] }}</th>
            <th style="text-align:center;">{{ $t['result'] }}</th>
            <th>{{ $t['finding'] }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rec->ortho_tests as $test)
        <tr>
            <td>{{ $test['name'] ?? '' }}</td>
            <td style="text-align:center;">
                <span class="badge {{ ($test['result'] ?? '') === '+' ? 'badge-red' : 'badge-green' }}">
                    {{ $test['result'] ?? '—' }}
                </span>
            </td>
            <td>{{ $test['finding'] ?? '' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

@if($rec->outcome_measures && !empty($rec->outcome_measures['type']) && $rec->outcome_measures['type'] !== 'none')
<div class="section-title">{{ $t['outcome_measures'] }}</div>
<div class="row">
    <span class="label">{{ strtoupper($rec->outcome_measures['type']) }} {{ $t['score'] }}:</span>
    <span class="value" style="font-weight:700; font-size:16px;">{{ $rec->outcome_measures['score'] ?? '—' }}</span>
</div>
@endif

@if($rec->body_map && count($rec->body_map))
<div class="section-title">{{ $t['body_map'] }}</div>
<div style="font-size:11px; color:#555;">
    @foreach($rec->body_map as $pt)
    <span class="badge badge-blue" style="margin:2px;">{{ $pt['label'] ?: ('(' . $pt['x'] . ',' . $pt['y'] . ')') }} — Severity {{ $pt['severity'] }}</span>
    @endforeach
</div>
@endif

<div class="sig-block">
    <div class="sig-line">
        <hr>
        <div style="font-size:11px; color:#555;">{{ $t['doctor_signature'] }}</div>
    </div>
</div>
@endsection
