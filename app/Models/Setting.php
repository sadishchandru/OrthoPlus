<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    public const CACHE_KEY = 'app_settings_all';

    /** All settings as key => decoded value (cached forever, busted on write). */
    public static function allCached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return self::all()->mapWithKeys(fn($s) => [
                $s->key => json_decode($s->value, true),
            ])->all();
        });
    }

    public static function get(string $key, $default = null)
    {
        return self::allCached()[$key] ?? $default;
    }

    /** Bulk upsert: [key => value, ...]. Values JSON-encoded. Clears cache once. */
    public static function putMany(array $pairs, string $group = 'general'): void
    {
        foreach ($pairs as $key => $value) {
            self::updateOrCreate(
                ['key' => $key],
                ['value' => json_encode($value), 'group' => $group]
            );
        }
        Cache::forget(self::CACHE_KEY);
    }
}
