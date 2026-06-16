@php
require_once resource_path('views/print/_helpers.php');
$t = printLang(request('lang', 'en'));
@endphp
@extends('print.layout')
@section('title', $t['consent_title'])

@section('content')
<h2 style="font-size:18px; font-weight:700; color:#1e3a8a; text-align:center; margin-bottom:20px;">
    {{ $t['consent_title'] }}
</h2>

<div style="border:1px solid #e5e7eb; border-radius:8px; padding:20px; margin-bottom:20px;">
    <p style="line-height:1.8; color:#374151; font-size:13px;">{{ $t['consent_text'] }}</p>
</div>

<!-- Patient details fillable -->
<div class="section-title">{{ $t['patient_name'] }}</div>
<div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px;">
    <div>
        <div style="font-size:12px; color:#6b7280; margin-bottom:2px;">{{ $t['patient_name'] }}</div>
        <div style="border-bottom:1px solid #374151; height:24px;"></div>
    </div>
    <div>
        <div style="font-size:12px; color:#6b7280; margin-bottom:2px;">{{ $t['op_number'] }}</div>
        <div style="border-bottom:1px solid #374151; height:24px;"></div>
    </div>
    <div>
        <div style="font-size:12px; color:#6b7280; margin-bottom:2px;">{{ $t['phone'] }}</div>
        <div style="border-bottom:1px solid #374151; height:24px;"></div>
    </div>
    <div>
        <div style="font-size:12px; color:#6b7280; margin-bottom:2px;">{{ $t['date'] }}</div>
        <div style="border-bottom:1px solid #374151; height:24px;"></div>
    </div>
</div>

<!-- Consent clauses -->
<div style="margin-bottom:20px; space-y: 8px;">
    @foreach([
        'I understand the nature of the proposed treatment and associated risks.',
        'I consent to photographs/videos being taken for medical documentation.',
        'I understand that treatment outcomes cannot be guaranteed.',
        'I agree to inform the treating clinician of any changes in my condition.',
    ] as $clause)
    <div style="display:flex; align-items:flex-start; gap:10px; margin-bottom:10px;">
        <div style="width:16px; height:16px; border:1px solid #374151; flex-shrink:0; margin-top:2px; border-radius:2px;"></div>
        <p style="font-size:12px; color:#374151; line-height:1.5;">{{ $clause }}</p>
    </div>
    @endforeach
</div>

<!-- Signatures -->
<div style="display:grid; grid-template-columns:1fr 1fr; gap:40px; margin-top:40px;">
    <div>
        <div style="border-bottom:1px solid #374151; height:50px; margin-bottom:6px;"></div>
        <div style="font-size:11px; color:#555; text-align:center;">{{ $t['patient_signature'] }} &amp; Date</div>
    </div>
    <div>
        <div style="border-bottom:1px solid #374151; height:50px; margin-bottom:6px;"></div>
        <div style="font-size:11px; color:#555; text-align:center;">{{ $t['doctor_signature'] }} &amp; Date</div>
    </div>
</div>
@endsection
