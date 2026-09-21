<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    public const SUPPLY_ISSUE_SCHEDULE_RESTRICTION = 'supplies.issue_schedule_restriction_enabled';

    protected $fillable = ['key', 'value'];

    public static function boolean(string $key, bool $default = false): bool
    {
        return (bool) Cache::rememberForever(self::cacheKey($key), function () use ($key, $default) {
            $value = static::query()->where('key', $key)->value('value');

            return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
        });
    }

    public static function putBoolean(string $key, bool $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value ? '1' : '0']);
        Cache::forget(self::cacheKey($key));
    }

    public static function forgetCached(string $key): void
    {
        Cache::forget(self::cacheKey($key));
    }

    private static function cacheKey(string $key): string
    {
        return 'system_setting.' . $key;
    }
}
