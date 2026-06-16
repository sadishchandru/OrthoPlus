<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'OrthoPlus')</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #1a1a1a; background: #fff; }
        .page { max-width: 210mm; margin: 0 auto; padding: 16mm 14mm; }
        .clinic-header { border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: flex-start; }
        .clinic-name { font-size: 20px; font-weight: 700; color: #1e3a8a; }
        .clinic-sub { font-size: 11px; color: #555; margin-top: 2px; }
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #1e3a8a; border-bottom: 1px solid #dbeafe; padding-bottom: 3px; margin: 14px 0 6px; }
        .row { display: flex; gap: 8px; margin-bottom: 5px; }
        .label { font-weight: 600; color: #374151; min-width: 110px; }
        .value { color: #1a1a1a; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th { background: #eff6ff; font-weight: 600; text-align: left; padding: 5px 8px; border: 1px solid #dbeafe; }
        td { padding: 4px 8px; border: 1px solid #e5e7eb; vertical-align: top; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-green { background: #dcfce7; color: #166534; }
        .footer { margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 8px; font-size: 10px; color: #9ca3af; text-align: center; }
        .sig-block { display: flex; justify-content: flex-end; margin-top: 40px; }
        .sig-line { text-align: center; min-width: 160px; }
        .sig-line hr { border: none; border-top: 1px solid #374151; margin-bottom: 4px; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
    </style>
    @yield('head')
</head>
<body>
<div class="page">
    <!-- Clinic header -->
    <div class="clinic-header">
        <div>
            <div class="clinic-name">{{ config('clinic.name', 'OrthoPlus Clinic') }}</div>
            <div class="clinic-sub">{{ config('clinic.tagline', 'Orthopedic & Physiotherapy Care') }}</div>
            <div class="clinic-sub">{{ config('clinic.address', '') }}</div>
        </div>
        <div style="text-align:right; font-size:11px; color:#555;">
            <div>{{ config('clinic.phone', '') }}</div>
            <div>{{ config('clinic.email', '') }}</div>
            <div style="margin-top:4px; font-weight:600;">Date: {{ now()->format('d M Y') }}</div>
        </div>
    </div>

    @yield('content')

    <div class="footer">
        OrthoPlus Clinic Management System &mdash; Confidential Patient Document
    </div>
</div>
</body>
</html>
