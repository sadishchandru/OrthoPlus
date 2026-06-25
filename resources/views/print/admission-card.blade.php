@php
require_once resource_path('views/print/_helpers.php');
$t   = printLang(request('lang', 'en'));
$adm = \App\Models\Admission::with(['patient', 'ward', 'bed'])->findOrFail($id);
$pat = $adm->patient;
@endphp
<!DOCTYPE html>
<html lang="{{ request('lang', 'en') }}">
<head>
<meta charset="utf-8">
<title>{{ $t['admission_card'] }} — {{ $pat->name ?? '' }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f3f4f6; }
    .card { width: 86mm; height: 54mm; margin: 0 auto; background: #fff; border: 1px solid #2E7D32; border-radius: 10px; overflow: hidden; display: flex; flex-direction: column; }
    .top { background: #2E7D32; color: #fff; padding: 4px 10px; display:flex; justify-content:space-between; align-items:center; }
    .top .c { font-size: 13px; font-weight: 700; }
    .top .ip { font-size: 11px; font-family:'Courier New',monospace; }
    .body { flex: 1; display: flex; padding: 8px 10px; gap: 10px; }
    .photo { width: 22mm; height: 28mm; border: 1px solid #cbd5e1; border-radius: 4px; object-fit: cover; background:#f1f5f9; flex-shrink:0; }
    .info { flex: 1; font-size: 11px; }
    .info .name { font-size: 15px; font-weight: 700; color:#2E7D32; margin-bottom: 4px; }
    .info .r { padding: 1px 0; }
    .info .k { color: #64748b; }
    .btn { margin: 10px auto; display:block; padding:8px 16px; background:#2E7D32; color:#fff; border:none; border-radius:6px; font-size:13px; cursor:pointer; }
    @media print { body { background:#fff; } .no-print { display:none !important; } @page { margin: 6mm; } }
</style>
</head>
<body>
<button class="btn no-print" onclick="window.print()">{{ $t['print'] ?? 'Print' }}</button>
<div class="card">
    <div class="top">
        <span class="c">{{ config('clinic.name', 'OrthoPlus') }}</span>
        <span class="ip">{{ $adm->ip_number }}</span>
    </div>
    <div class="body">
        @if($pat && $pat->photo)<img class="photo" src="{{ $pat->photo }}" alt="">@else<div class="photo"></div>@endif
        <div class="info">
            <div class="name">{{ $pat->name ?? '—' }}</div>
            <div class="r"><span class="k">{{ $t['op_number'] }}:</span> {{ $pat->op_number ?? '—' }}</div>
            <div class="r"><span class="k">{{ $t['gender'] }}:</span> {{ ucfirst($pat->gender ?? '—') }}</div>
            <div class="r"><span class="k">{{ $t['ward'] }} / {{ $t['bed'] }}:</span> {{ $adm->ward->name ?? '—' }} / {{ $adm->bed->bed_number ?? '—' }}</div>
            <div class="r"><span class="k">{{ $t['admission_date'] }}:</span> {{ $adm->admission_date ? \Carbon\Carbon::parse($adm->admission_date)->format('d M Y') : '—' }}</div>
            <div class="r"><span class="k">{{ $t['phone'] }}:</span> {{ $pat->phone ?? '—' }}</div>
        </div>
    </div>
</div>
</body>
</html>
