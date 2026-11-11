<?php

namespace App\Services;

use App\Models\Setting;

class SettingService
{
    /** @var array<string, string|null> */
    private array $cache = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (! array_key_exists($key, $this->cache)) {
            $this->cache[$key] = Setting::find($key)?->value;
        }

        return $this->cache[$key] ?? $default;
    }

    public function bool(string $key): bool
    {
        return filter_var($this->get($key, '0'), FILTER_VALIDATE_BOOLEAN);
    }

    public function set(string $key, ?string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        $this->cache[$key] = $value;
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    public function many(array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = $this->get($key);
        }

        return $out;
    }
}
