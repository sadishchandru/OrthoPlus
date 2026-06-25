@php
require_once resource_path('views/print/_helpers.php');
$t   = printLang(request('lang', 'en'));
$q   = \App\Models\OpdQueue::with('patient')->findOrFail($id);
$pat = $q->patient;
$emergency = $q->priority === 'emergency';
@endphp
<!DOCTYPE html>
<html lang="{{ request('lang', 'en') }}">
<head>
<meta charset="utf-8">
<title>{{ $t['opd_token'] }} #{{ $q->token_number }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f3f4f6; }
    /* 80mm thermal */
    .slip { width: 80mm; margin: 0 auto; background: #fff; padding: 6mm 5mm; text-align: center; }
    .clinic { font-size: 15px; font-weight: 700; }
    .sub { font-size: 10px; color: #555; margin-bottom: 6px; }
    hr { border: none; border-top: 1px dashed #999; margin: 6px 0; }
    .lbl { font-size: 11px; color: #555; letter-spacing: .1em; text-transform: uppercase; }
    .token { font-size: 64px; font-weight: 800; line-height: 1; margin: 4px 0; }
    .emerg { color: #b91c1c; }
    .name { font-size: 16px; font-weight: 600; margin-top: 6px; }
    .meta { font-size: 11px; color: #555; margin-top: 2px; }
    .flag { display:inline-block; margin-top:6px; font-size:12px; font-weight:700; color:#b91c1c; }
    .btn { margin: 10px auto; display:block; padding:8px 16px; background:#2E7D32; color:#fff; border:none; border-radius:6px; font-size:13px; cursor:pointer; }
    @media print { body { background:#fff; } .no-print { display:none !important; } @page { margin: 0; } }
</style>
</head>
<body>
<button class="btn no-print" onclick="window.print()">{{ $t['print'] ?? 'Print' }}</button>
<div class="slip">
    <div class="clinic">{{ config('clinic.name', 'OrthoPlus') }}</div>
    <div class="sub">{{ $t['opd_token'] }} · {{ \Carbon\Carbon::parse($q->date)->format('d M Y') }}</div>
    <hr>
    <div class="lbl">{{ $t['token'] }}</div>
    <div class="token {{ $emergency ? 'emerg' : '' }}">{{ str_pad($q->token_number, 3, '0', STR_PAD_LEFT) }}</div>
    @if($emergency)<div class="flag">🚨 EMERGENCY</div>@elseif($q->priority === 'urgent')<div class="flag" style="color:#c2410c;">URGENT</div>@endif
    <div class="name">{{ $pat->name ?? '—' }}</div>
    <div class="meta">{{ $t['op_number'] }}: {{ $pat->op_number ?? '—' }}</div>
    <hr>
    <div class="meta">{{ \Carbon\Carbon::parse($q->arrival_time ?? now())->format('d M Y, h:i A') }}</div>
</div>
</body>
</html>
