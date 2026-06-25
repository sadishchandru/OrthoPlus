<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /** Merged branding (DB over config defaults). Used by the Settings UI. */
    public function show()
    {
        return response()->json(branding());
    }

    /** Bulk-save any of the three groups (theme/brand/print). Root only. */
    public function update(Request $request)
    {
        $data = $request->validate([
            'theme' => 'nullable|array',
            'brand' => 'nullable|array',
            'print' => 'nullable|array',
        ]);

        $pairs = [];
        foreach (['theme', 'brand', 'print'] as $group) {
            if (array_key_exists($group, $data) && $data[$group] !== null) {
                // Merge over defaults so partial saves never drop keys.
                $pairs[$group] = array_replace_recursive(
                    config("branding.$group", []),
                    Setting::get($group, []),
                    $data[$group]
                );
            }
        }

        if ($pairs) {
            Setting::putMany($pairs, 'branding');
        }

        return response()->json(branding());
    }
}
