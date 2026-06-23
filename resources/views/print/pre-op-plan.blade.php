@php
require_once resource_path('views/print/_helpers.php');
$t   = printLang(request('lang', 'en'));
$p   = \App\Models\PreOpPlan::with(['patient', 'surgery'])->findOrFail($id);
$pat = $p->patient;
@endphp
@extends('print.layout')
@section('title', $t['pre_op_plan'] . ' — ' . ($pat->name ?? ''))

@section('content')
<h2 style="font-size:18px; font-weight:700; color:#1e3a8a; margin-bottom:8px;">{{ $t['pre_op_plan'] }}</h2>

<div style="background:#eff6ff; border-radius:8px; padding:10px 12px; margin-bottom:14px; font-size:12px;">
    <div class="row"><span class="label">{{ $t['patient_name'] }}:</span><span class="value">{{ $pat->name ?? '—' }} ({{ $pat->op_number ?? '—' }})</span></div>
    <div class="row"><span class="label">{{ $t['procedure'] }}:</span><span class="value">{{ $p->surgery->surgery_name ?? '—' }}</span></div>
    <div class="row"><span class="label">{{ $t['template'] }}:</span><span class="value" style="text-transform:capitalize;">{{ $p->template_type ?: '—' }}</span></div>
</div>

<div class="section-title">{{ $t['plan_details'] }}</div>
@if(!empty($p->plan_data))
<table><tbody>
@foreach($p->plan_data as $k => $v)
<tr><td style="font-weight:600; width:40%; text-transform:capitalize;">{{ is_string($k) ? str_replace('_', ' ', $k) : $loop->index }}</td><td>{{ is_array($v) ? implode(', ', $v) : $v }}</td></tr>
@endforeach
</tbody></table>
@else<p style="font-size:12px;">—</p>@endif

@if(!empty($p->implant_selections))
<div class="section-title">{{ $t['implant_charges'] }}</div>
<ul style="font-size:12px; margin-left:18px;">@foreach($p->implant_selections as $im)<li>{{ is_array($im) ? implode(' — ', $im) : $im }}</li>@endforeach</ul>
@endif

<div class="section-title">{{ $t['simulation'] }}</div>
<p style="font-size:12px;">{{ $p->simulation_notes ?: '—' }}</p>

<div class="sig-block" style="margin-top:50px;"><div class="sig-line"><hr><div style="font-size:11px; color:#555;">{{ $t['surgeon'] }}</div></div></div>
@endsection
