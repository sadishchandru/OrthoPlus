@php
require_once resource_path('views/print/_helpers.php');
$t   = printLang(request('lang', 'en'));
$inv = \App\Models\Invoice::with('patient')->findOrFail($id);
$pat = $inv->patient;
@endphp
@extends('print.layout')
@section('title', $t['invoice'] . ' ' . $inv->invoice_no)

@section('content')
<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:14px;">
    <div>
        <h2 style="font-size:18px; font-weight:700; color:#1e3a8a;">{{ $t['invoice'] }}</h2>
        <div style="font-size:12px; color:#555; margin-top:4px;">
            <div><strong>{{ $t['invoice_no'] }}:</strong> {{ $inv->invoice_no }}</div>
            <div><strong>{{ $t['date'] }}:</strong> {{ \Carbon\Carbon::parse($inv->created_at)->format('d M Y') }}</div>
        </div>
    </div>
    <div>
        <span class="badge {{ $inv->status === 'paid' ? 'badge-green' : ($inv->status === 'due' ? 'badge-red' : 'badge-blue') }}" style="font-size:13px; padding:4px 12px;">
            {{ strtoupper($inv->status) }}
        </span>
    </div>
</div>

<div style="background:#eff6ff; border-radius:8px; padding:10px 12px; margin-bottom:14px; font-size:12px;">
    <div><strong>{{ $t['patient_name'] }}:</strong> {{ $pat->name }}</div>
    <div><strong>{{ $t['op_number'] }}:</strong> {{ $pat->op_number }} &nbsp; <strong>{{ $t['phone'] }}:</strong> {{ $pat->phone }}</div>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>{{ $t['description'] }}</th>
            <th style="text-align:right;">{{ $t['qty'] }}</th>
            <th style="text-align:right;">{{ $t['rate'] }} (₹)</th>
            <th style="text-align:right;">{{ $t['amount'] }} (₹)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($inv->items as $i => $item)
        <tr>
            <td style="text-align:center;">{{ $i + 1 }}</td>
            <td>{{ $item['description'] }}</td>
            <td style="text-align:right;">{{ $item['qty'] }}</td>
            <td style="text-align:right;">{{ number_format($item['rate'], 2) }}</td>
            <td style="text-align:right;">{{ number_format($item['amount'] ?? ($item['qty'] * $item['rate']), 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<!-- Totals -->
<div style="display:flex; justify-content:flex-end; margin-top:10px;">
    <table style="width:260px; border:none;">
        <tr>
            <td style="border:none; padding:3px 8px; color:#6b7280;">{{ $t['subtotal'] }}</td>
            <td style="border:none; padding:3px 8px; text-align:right;">₹{{ number_format($inv->subtotal, 2) }}</td>
        </tr>
        @if($inv->discount > 0)
        <tr>
            <td style="border:none; padding:3px 8px; color:#6b7280;">{{ $t['discount'] }}</td>
            <td style="border:none; padding:3px 8px; text-align:right; color:#16a34a;">- ₹{{ number_format($inv->discount, 2) }}</td>
        </tr>
        @endif
        @if($inv->tax > 0)
        <tr>
            <td style="border:none; padding:3px 8px; color:#6b7280;">{{ $t['tax'] }}</td>
            <td style="border:none; padding:3px 8px; text-align:right;">₹{{ number_format($inv->tax, 2) }}</td>
        </tr>
        @endif
        <tr style="border-top: 2px solid #1e3a8a;">
            <td style="border:none; padding:6px 8px; font-weight:700; font-size:14px; color:#1e3a8a;">{{ $t['total'] }}</td>
            <td style="border:none; padding:6px 8px; text-align:right; font-weight:700; font-size:16px; color:#1e3a8a;">₹{{ number_format($inv->total, 2) }}</td>
        </tr>
    </table>
</div>

@if($inv->payment_method)
<div style="margin-top:10px; font-size:12px; color:#555;">
    <strong>{{ $t['payment_method'] }}:</strong> {{ $inv->payment_method }}
</div>
@endif

<div class="sig-block" style="margin-top:40px;">
    <div class="sig-line">
        <hr>
        <div style="font-size:11px; color:#555;">Authorised Signatory</div>
    </div>
</div>
@endsection
