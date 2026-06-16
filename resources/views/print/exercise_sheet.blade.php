@php
require_once resource_path('views/print/_helpers.php');
$t    = printLang(request('lang', 'en'));
$prec = \App\Models\ExercisePrescription::with(['patient', 'clinicalRecord'])->findOrFail($id);
$pat  = $prec->patient;
$exerciseIds = collect($prec->exercises)->pluck('exercise_id')->filter()->toArray();
$exerciseMap = \App\Models\Exercise::whereIn('id', $exerciseIds)->get()->keyBy('id');
@endphp
@extends('print.layout')
@section('title', $t['exercise_sheet'] . ' — ' . $pat->name)

@section('content')
<h2 style="font-size:16px; font-weight:700; color:#1e3a8a; margin-bottom:8px;">{{ $t['exercise_sheet'] }}</h2>

<div style="display:flex; gap:24px; margin-bottom:12px; font-size:12px;">
    <div><strong>{{ $t['patient_name'] }}:</strong> {{ $pat->name }} ({{ $pat->op_number }})</div>
    <div><strong>{{ $t['date'] }}:</strong> {{ \Carbon\Carbon::parse($prec->created_at)->format('d M Y') }}</div>
    @if($prec->frequency)
    <div><strong>{{ $t['frequency'] }}:</strong> {{ $prec->frequency }}</div>
    @endif
</div>

@foreach($prec->exercises as $i => $ex)
@php $exercise = $exerciseMap[$ex['exercise_id']] ?? null; @endphp
@if($exercise)
<div style="border:1px solid #e5e7eb; border-radius:8px; padding:12px; margin-bottom:12px; page-break-inside:avoid;">
    <div style="display:flex; gap:12px; align-items:flex-start;">
        @if($exercise->image_url)
        <img src="{{ $exercise->image_url }}" style="width:80px; height:80px; object-fit:cover; border-radius:6px; flex-shrink:0;" />
        @else
        <div style="width:80px; height:80px; background:#eff6ff; border-radius:6px; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:28px;">🏋️</div>
        @endif
        <div style="flex:1;">
            <div style="font-weight:700; font-size:14px; color:#1e3a8a; margin-bottom:4px;">{{ $i+1 }}. {{ $exercise->name }}</div>
            <div style="display:flex; gap:16px; font-size:12px; margin-bottom:6px;">
                @if(!empty($ex['sets']))<span><strong>{{ $t['sets'] }}:</strong> {{ $ex['sets'] }}</span>@endif
                @if(!empty($ex['reps']))<span><strong>{{ $t['reps'] }}:</strong> {{ $ex['reps'] }}</span>@endif
                @if(!empty($ex['hold']))<span><strong>{{ $t['hold'] }}:</strong> {{ $ex['hold'] }}s</span>@endif
            </div>
            @if($exercise->instructions)
            <p style="font-size:12px; color:#374151; line-height:1.5;">{{ $exercise->instructions }}</p>
            @endif
            @if(!empty($ex['notes']))
            <p style="font-size:11px; color:#6b7280; margin-top:4px; font-style:italic;">{{ $ex['notes'] }}</p>
            @endif
        </div>
    </div>
</div>
@endif
@endforeach

@if($prec->notes)
<div class="section-title">{{ $t['notes'] }}</div>
<p style="color:#374151; line-height:1.6;">{{ $prec->notes }}</p>
@endif

<div class="sig-block">
    <div class="sig-line">
        <hr>
        <div style="font-size:11px; color:#555;">{{ $t['doctor_signature'] }}</div>
    </div>
</div>
@endsection
