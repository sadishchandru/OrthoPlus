<?php

/**
 * OrthoPlus branding/theme/print DEFAULTS.
 * DB `settings` (keys: theme, brand, print) override these per group.
 * Edit here only to change shipped defaults; admins change live via Settings.
 */
return [
    'theme' => [
        'primary'     => '#2E7D32',
        'secondary'   => '#4CAF50',
        'accent'      => '#81C784',
        'background'  => '#F8FAF8',
        'text'        => '#1F2937',
        'sidebar'     => '#1F5523',
        'header'      => '#1F5523',
        'font_family' => "'Instrument Sans', ui-sans-serif, system-ui, sans-serif",
        'font_size'   => 16,   // px base
        'radius'      => 12,   // px (rounded-lg/xl scale)
        'dark'        => false,
        'status'      => [
            'waiting'     => '#F59E0B',
            'in_progress' => '#2E7D32',
            'completed'   => '#1B5E20',
            'cancelled'   => '#DC2626',
        ],
    ],

    'brand' => [
        'name'          => 'OrthoPlus',
        'tagline'       => 'Orthopedic & Physiotherapy',
        'logo'          => null,   // data-URL or /storage path; null → built-in SVG mark
        'address'       => '',
        'phone'         => '',
        'email'         => '',
        'website'       => '',
        'reg_no'        => '',
        'gst_no'        => '',
        'accreditation' => '',  // NABH / ISO
    ],

    'print' => [
        'header_style' => 'classic',  // classic | modern | premium | minimal
        'show_logo'    => true,
        'show_footer'  => true,
        'footer'       => 'OrthoPlus — Confidential Patient Document',
        'watermark'    => '',
        'signature'    => 'Authorised Signatory',
        'stamp'        => '',         // optional stamp image data-URL
        'margins'      => ['top' => 16, 'right' => 14, 'bottom' => 16, 'left' => 14], // mm
        'font'         => 'Segoe UI, Arial, sans-serif',
        'font_size'    => 13,         // px
        'color'        => '#2E7D32',  // accent/heading color
    ],
];
