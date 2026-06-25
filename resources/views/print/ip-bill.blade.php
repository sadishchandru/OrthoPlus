@php
require_once resource_path('views/print/_helpers.php');
$t    = printLang(request('lang', 'en'));
$bill = \App\Models\IpBill::with(['patient', 'admission.ward', 'admission.bed'])->findOrFail($id);
$pat  = $bill->patient;
$adm  = $bill->admission;
$lines = [
    [$t['room_charges'],     $bill->room_charges],
    [$t['pharmacy_charges'], $bill->pharmacy_charges],
    [$t['surgery_charges'],  $bill->surgery_charges],
    [$t['implant_charges'],  $bill->implant_charges],
    [$t['misc_charges'],     $bill->misc_charges],
];
@endphp
@extends('print.layout')
@section('title', $t['ip_bill'] . ' #' . $bill->id)

@section('content')
<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:14px;">
    <div>
        <h2 style="font-size:18px; font-weight:700; color:#2E7D32;">{{ $t['ip_bill'] }}</h2>
        <div style="font-size:12px; color:#555; margin-top:4px;">
            <div><strong>{{ $t['bill_no'] }}:</strong> {{ $bill->id }}</div>
            <div><strong>{{ $t['bill_date'] }}:</strong> {{ \Carbon\Carbon::parse($bill->bill_date)->format('d M Y') }}</div>
            <div><strong>{{ $t['ip_number'] }}:</strong> {{ $adm->ip_number ?? '—' }}</div>
        </div>
    </div>
    <span class="badge {{ $bill->status === 'paid' ? 'badge-green' : ($bill->balance > 0 ? 'badge-red' : 'badge-blue') }}" style="font-size:13px; padding:4px 12px;">
        {{ strtoupper($bill->status) }}
    </span>
</div>

<div style="background:#F1F8F2; border-radius:8px; padding:10px 12px; margin-bottom:14px; font-size:12px;">
    <div><strong>{{ $t['patient_name'] }}:</strong> {{ $pat->name ?? '—' }} &nbsp; <strong>{{ $t['op_number'] }}:</strong> {{ $pat->op_number ?? '—' }}</div>
    <div><strong>{{ $t['ward'] }}:</strong> {{ $adm->ward->name ?? '—' }} &nbsp; <strong>{{ $t['bed'] }}:</strong> {{ $adm->bed->bed_number ?? '—' }}</div>
</div>

<table>
    <thead><tr><th>{{ $t['description'] }}</th><th style="text-align:right;">{{ $t['amount'] }} (₹)</th></tr></thead>
    <tbody>
        @foreach($lines as $l)
        <tr><td>{{ $l[0] }}</td><td style="text-align:right;">{{ number_format($l[1], 2) }}</td></tr>
        @endforeach
        @foreach(($bill->line_items ?? []) as $item)
        <tr><td>{{ $item['description'] ?? '—' }}</td><td style="text-align:right;">{{ number_format($item['amount'] ?? 0, 2) }}</td></tr>
        @endforeach
    </tbody>
</table>

<div style="display:flex; justify-content:flex-end; margin-top:10px;">
    <table style="width:280px; border:none;">
        <tr><td style="border:none; padding:3px 8px; color:#6b7280;">{{ $t['subtotal'] }}</td><td style="border:none; padding:3px 8px; text-align:right;">₹{{ number_format($bill->subtotal, 2) }}</td></tr>
        @if($bill->discount > 0)<tr><td style="border:none; padding:3px 8px; color:#6b7280;">{{ $t['discount'] }}</td><td style="border:none; padding:3px 8px; text-align:right; color:#16a34a;">- ₹{{ number_format($bill->discount, 2) }}</td></tr>@endif
        @if($bill->gst > 0)<tr><td style="border:none; padding:3px 8px; color:#6b7280;">{{ $t['tax'] }}</td><td style="border:none; padding:3px 8px; text-align:right;">₹{{ number_format($bill->gst, 2) }}</td></tr>@endif
        <tr style="border-top:2px solid #2E7D32;"><td style="border:none; padding:6px 8px; font-weight:700; font-size:14px; color:#2E7D32;">{{ $t['total'] }}</td><td style="border:none; padding:6px 8px; text-align:right; font-weight:700; font-size:16px; color:#2E7D32;">₹{{ number_format($bill->total, 2) }}</td></tr>
        <tr><td style="border:none; padding:3px 8px; color:#6b7280;">{{ $t['paid'] }}</td><td style="border:none; padding:3px 8px; text-align:right;">₹{{ number_format($bill->paid, 2) }}</td></tr>
        <tr><td style="border:none; padding:3px 8px; color:#6b7280; font-weight:600;">{{ $t['balance'] }}</td><td style="border:none; padding:3px 8px; text-align:right; font-weight:700; color:{{ $bill->balance > 0 ? '#991b1b' : '#166534' }};">₹{{ number_format($bill->balance, 2) }}</td></tr>
    </table>
</div>

<div class="sig-block"><div class="sig-line"><hr><div style="font-size:11px; color:#555;">{{ $t['doctor_signature'] }}</div></div></div>
@endsection
