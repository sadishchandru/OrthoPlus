@php
require_once resource_path('views/print/_helpers.php');
$t   = printLang(request('lang', 'en'));
$s   = \App\Models\Surgery::with(['patient', 'admission'])->findOrFail($id);
$pat = $s->patient;
@endphp
@extends('print.layout')
@section('title', $t['surgery_note'] . ' — ' . ($pat->name ?? ''))

@section('content')
<h2 style="font-size:18px; font-weight:700; color:#2E7D32; margin-bottom:8px;">{{ $t['surgery_note'] }}</h2>

<div style="background:#F1F8F2; border-radius:8px; padding:10px 12px; margin-bottom:14px; font-size:12px;">
    <div class="row"><span class="label">{{ $t['patient_name'] }}:</span><span class="value">{{ $pat->name ?? '—' }} ({{ $pat->op_number ?? '—' }})</span></div>
    <div class="row"><span class="label">{{ $t['ip_number'] }}:</span><span class="value">{{ $s->admission->ip_number ?? '—' }}</span></div>
    <div class="row"><span class="label">{{ $t['procedure'] }}:</span><span class="value">{{ $s->surgery_name }} <span style="text-transform:capitalize;">({{ $s->surgery_type }})</span></span></div>
    <div class="row"><span class="label">{{ $t['scheduled'] }}:</span><span class="value">{{ $s->scheduled_date ? \Carbon\Carbon::parse($s->scheduled_date)->format('d M Y') : '—' }} {{ $s->scheduled_time }}</span></div>
    <div class="row"><span class="label">{{ $t['or_room'] }}:</span><span class="value">{{ $s->or_room ?: '—' }}</span></div>
</div>

@if(!empty($s->implants_required))
<div class="section-title">{{ $t['implant_charges'] }}</div>
<ul style="font-size:12px; margin-left:18px;">@foreach($s->implants_required as $im)<li>{{ is_array($im) ? implode(' — ', $im) : $im }}</li>@endforeach</ul>
@endif

<div class="section-title">{{ $t['instructions'] }} (Pre-op)</div>
<p style="font-size:12px;">{{ $s->pre_op_instructions ?: '—' }}</p>

<div class="section-title">{{ $t['post_op'] }}</div>
<p style="font-size:12px;">{{ $s->post_op_notes ?: '—' }}</p>

<div class="section-title">{{ $t['complications'] }}</div>
<p style="font-size:12px;">{{ $s->complications ?: 'None recorded' }}</p>

<div class="sig-block" style="margin-top:50px;"><div class="sig-line"><hr><div style="font-size:11px; color:#555;">{{ $t['surgeon'] }}</div></div></div>
@endsection
