<!DOCTYPE html>
@php $b = branding(); $t = $b['theme']; @endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ ($t['dark'] ?? false) ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $b['brand']['name'] ?? 'OrthoPlus' }} — {{ $b['brand']['tagline'] ?? 'Orthopedic & Physiotherapy' }}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <meta name="theme-color" content="{{ $t['primary'] }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Branding applied globally from DB settings — overrides app.css defaults, no rebuild, no boot API call. --}}
    <style id="brand-theme">
        :root {
            --color-blue-700: {{ $t['primary'] }};
            --color-blue-600: {{ $t['primary'] }};
            --color-blue-500: {{ $t['secondary'] }};
            --color-blue-400: {{ $t['secondary'] }};
            --color-blue-300: {{ $t['accent'] }};
            --color-gray-50:  {{ $t['background'] }};
            --font-sans: {{ $t['font_family'] }};
            --radius-lg: {{ (int) $t['radius'] }}px;
            --radius-xl: {{ (int) $t['radius'] + 2 }}px;
            --brand-primary: {{ $t['primary'] }};
            --brand-sidebar: {{ $t['sidebar'] }};
            --brand-header: {{ $t['header'] }};
            --status-waiting: {{ $t['status']['waiting'] }};
            --status-in-progress: {{ $t['status']['in_progress'] }};
            --status-completed: {{ $t['status']['completed'] }};
            --status-cancelled: {{ $t['status']['cancelled'] }};
        }
        html { font-size: {{ (int) $t['font_size'] }}px; }
        html.dark body { background: #0f1410; color: #e5e7eb; }
    </style>
    <script>window.__BRAND__ = @json($b);</script>
</head>
<body>
    <div id="app"></div>
</body>
</html>
