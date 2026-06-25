@php
    $b = branding();
    $brand = $b['brand'];
    $p = $b['print'];
    $accent = $p['color'] ?? '#2E7D32';
    $style = $p['header_style'] ?? 'classic';
    $m = $p['margins'] ?? ['top' => 16, 'right' => 14, 'bottom' => 16, 'left' => 14];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $brand['name'] ?? 'OrthoPlus')</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: {{ $p['font'] ?? "'Segoe UI', Arial, sans-serif" }}; font-size: {{ (int)($p['font_size'] ?? 13) }}px; color: #1a1a1a; background: #fff; }
        .page { max-width: 210mm; margin: 0 auto; padding: {{ (int)$m['top'] }}mm {{ (int)$m['right'] }}mm {{ (int)$m['bottom'] }}mm {{ (int)$m['left'] }}mm; position: relative; }
        .clinic-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; padding-bottom: 10px; margin-bottom: 14px; }
        .clinic-name { font-size: 20px; font-weight: 700; color: {{ $accent }}; }
        .clinic-sub { font-size: 11px; color: #555; margin-top: 2px; }
        .clinic-right { text-align: right; font-size: 10.5px; color: #555; }
        .clinic-logo { width: 46px; height: 46px; object-fit: contain; }
        /* Header styles */
        .style--classic .clinic-header { border-bottom: 2px solid {{ $accent }}; }
        .style--modern  .clinic-header { background: {{ $accent }}; color: #fff; padding: 10px 12px; border-radius: 8px; }
        .style--modern  .clinic-name, .style--modern .clinic-sub, .style--modern .clinic-right { color: #fff; }
        .style--premium .page { border-top: 6px solid {{ $accent }}; }
        .style--premium .clinic-header { border-bottom: 1px solid #e5e7eb; }
        .style--premium .clinic-name { font-size: 24px; letter-spacing: .01em; }
        .style--minimal .clinic-header { border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; }
        .style--minimal .clinic-name { font-size: 16px; }
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: {{ $accent }}; border-bottom: 1px solid #DCEFDD; padding-bottom: 3px; margin: 14px 0 6px; }
        .row { display: flex; gap: 8px; margin-bottom: 5px; }
        .label { font-weight: 600; color: #374151; min-width: 110px; }
        .value { color: #1a1a1a; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th { background: #F1F8F2; font-weight: 600; text-align: left; padding: 5px 8px; border: 1px solid #DCEFDD; }
        td { padding: 4px 8px; border: 1px solid #e5e7eb; vertical-align: top; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600; }
        .badge-blue { background: #DCEFDD; color: #276B2B; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-green { background: #dcfce7; color: #166534; }
        .footer { margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 8px; font-size: 10px; color: #9ca3af; text-align: center; }
        .sig-block { display: flex; justify-content: flex-end; margin-top: 40px; }
        .sig-line { text-align: center; min-width: 160px; }
        .sig-line hr { border: none; border-top: 1px solid #374151; margin-bottom: 4px; }
        .watermark { position: fixed; top: 50%; left: 50%; transform: translate(-50%,-50%) rotate(-30deg); font-size: 80px; font-weight: 800; color: {{ $accent }}; opacity: 0.06; pointer-events: none; white-space: nowrap; z-index: 0; }
        .page > * { position: relative; z-index: 1; }
        @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } .no-print { display: none !important; } }
    </style>
    @yield('head')
</head>
<body class="style--{{ $style }}">
@if(!empty($p['watermark']))<div class="watermark">{{ $p['watermark'] }}</div>@endif
<div class="page">
    <div class="clinic-header">
        <div style="display:flex; align-items:center; gap:10px;">
            @if(($p['show_logo'] ?? true))
                @if(!empty($brand['logo']))
                    <img src="{{ $brand['logo'] }}" alt="logo" class="clinic-logo">
                @else
                    <svg width="44" height="44" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
                        <path d="M52 18 A28 28 0 1 0 53 44" fill="none" stroke="{{ $accent }}" stroke-width="4" stroke-linecap="round"/>
                        <path d="M16 44 q16 16 32 2 q-6 12 -20 11 q-12 -1 -12 -13 Z" fill="{{ $accent }}"/>
                        <g fill="{{ $accent }}"><circle cx="30" cy="14" r="2"/><circle cx="31" cy="20" r="2.6"/><circle cx="30.5" cy="26.5" r="3.1"/><circle cx="31.5" cy="33" r="3.6"/><circle cx="31" cy="40" r="4.1"/><circle cx="32" cy="47" r="4.6"/></g>
                    </svg>
                @endif
            @endif
            <div>
                <div class="clinic-name">{{ $brand['name'] ?: 'OrthoPlus' }}</div>
                @if($brand['tagline'])<div class="clinic-sub">{{ $brand['tagline'] }}</div>@endif
                @if($brand['address'])<div class="clinic-sub">{{ $brand['address'] }}</div>@endif
                @if($brand['phone'] || $brand['email'] || $brand['website'])
                <div class="clinic-sub">{{ collect([$brand['phone'], $brand['email'], $brand['website']])->filter()->implode(' · ') }}</div>
                @endif
            </div>
        </div>
        <div class="clinic-right">
            @if($brand['reg_no'])<div>Reg No: {{ $brand['reg_no'] }}</div>@endif
            @if($brand['gst_no'])<div>GST: {{ $brand['gst_no'] }}</div>@endif
            @if($brand['accreditation'])<div>{{ $brand['accreditation'] }}</div>@endif
            <div style="margin-top:4px; font-weight:600;">Date: {{ now()->format('d M Y') }}</div>
        </div>
    </div>

    @yield('content')

    @if(($p['show_footer'] ?? true))
    <div class="footer">{{ $p['footer'] ?: ($brand['name'] . ' — Confidential Patient Document') }}</div>
    @endif
</div>
</body>
</html>
