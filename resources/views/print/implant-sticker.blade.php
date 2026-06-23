@php
require_once resource_path('views/print/_helpers.php');
$t  = printLang(request('lang', 'en'));
$im = \App\Models\Implant::findOrFail($id);
@endphp
<!DOCTYPE html>
<html lang="{{ request('lang', 'en') }}">
<head>
<meta charset="utf-8">
<title>{{ $t['implant_label'] }} — {{ $im->name }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f3f4f6; }
    .label { width: 60mm; margin: 0 auto; background: #fff; border: 1px solid #111; padding: 3mm 4mm; }
    .title { font-size: 9px; letter-spacing: .12em; text-transform: uppercase; color: #555; }
    .name { font-size: 14px; font-weight: 700; margin: 1px 0 3px; }
    .row { font-size: 10px; display: flex; justify-content: space-between; padding: 1px 0; border-top: 1px dotted #ccc; }
    .row .k { color: #555; }
    .row .v { font-weight: 600; }
    .ref { font-family: 'Courier New', monospace; font-size: 13px; font-weight: 700; letter-spacing: .08em; text-align:center; margin-top:3px; padding-top:3px; border-top:1px solid #111; }
    .btn { margin: 10px auto; display:block; padding:8px 16px; background:#1e3a8a; color:#fff; border:none; border-radius:6px; font-size:13px; cursor:pointer; }
    @media print { body { background:#fff; } .no-print { display:none !important; } @page { margin: 4mm; } }
</style>
</head>
<body>
<button class="btn no-print" onclick="window.print()">{{ $t['print'] ?? 'Print' }}</button>
<div class="label">
    <div class="title">{{ $t['implant_label'] }}</div>
    <div class="name">{{ $im->name }}</div>
    <div class="row"><span class="k">{{ $t['manufacturer'] }}</span><span class="v">{{ $im->manufacturer ?: '—' }}</span></div>
    <div class="row"><span class="k">{{ $t['size'] }}</span><span class="v">{{ $im->size ?: '—' }}{{ $im->side ? ' / '.$im->side : '' }}</span></div>
    <div class="row"><span class="k">{{ $t['batch'] }}</span><span class="v">{{ $im->batch_number ?: '—' }}</span></div>
    <div class="row"><span class="k">{{ $t['expiry'] }}</span><span class="v">{{ $im->expiry_date ? \Carbon\Carbon::parse($im->expiry_date)->format('M Y') : '—' }}</span></div>
    <div class="ref">{{ $t['ref_no'] }}: {{ $im->ref_number ?: $im->id }}</div>
</div>
</body>
</html>
