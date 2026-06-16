<?php
// Shared helper for print template translation
// Usage: $t = printLang(request('lang', 'en'));
function printLang(string $lang = 'en'): array
{
    $path = resource_path("lang/{$lang}/print.php");
    if (!file_exists($path)) {
        $path = resource_path('lang/en/print.php');
    }
    return require $path;
}
