<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdminDeveloper
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | Official Website: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | Licensed: https://mit-license.org
 * | Disclaimer: https://thinkadmin.top/disclaimer
 * | Vip Rights: https://thinkadmin.top/vip-introduce
 * +----------------------------------------------------------------------
 * | Gitee Repository: https://gitee.com/zoujingli/ThinkAdmin
 * | Github Repository: https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace plugin\system\storage;

class StorageConfig
{
    private const DATA_KEY = 'system.storage';

    private const GLOBAL_KEYS = [
        'driver' => 'default_driver',
        'default_driver' => 'default_driver',
        'naming' => 'naming_rule',
        'naming_rule' => 'naming_rule',
        'link' => 'link_mode',
        'link_mode' => 'link_mode',
        'allowed_exts' => 'allowed_extensions',
        'allowed_extensions' => 'allowed_extensions',
    ];

    public static function registry(): array
    {
        static $registry = [];
        if ($registry !== []) {
            return $registry;
        }
        $file = __DIR__ . '/extra/config.php';
        return $registry = is_file($file) ? include $file : [];
    }

    public static function initialize(): void
    {
        $current = self::readPayload();
        $payload = self::normalizePayload($current);
        if ($payload !== $current) {
            self::writePayload($payload);
        }
    }

    public static function global(string $name, mixed $default = null): mixed
    {
        $payload = self::payload();
        $key = self::GLOBAL_KEYS[$name] ?? $name;
        if (!array_key_exists($key, $payload)) {
            return $default;
        }
        if ($key === 'allowed_extensions') {
            return join(',', array_values($payload[$key]));
        }
        return $payload[$key];
    }

    public static function driver(string $driver, string $name, mixed $default = null): mixed
    {
        $payload = self::payload();
        return $payload['drivers'][$driver][$name] ?? null ?: $default;
    }

    public static function defaults(): array
    {
        $registry = static::registry();
        $defaults = [
            'default_driver' => strval($registry['default'] ?? 'local'),
            'naming_rule' => 'xmd5',
            'link_mode' => 'none',
            'allowed_extensions' => [],
            'drivers' => [],
        ];
        foreach (($registry['global'] ?? []) as $name => $meta) {
            $key = self::GLOBAL_KEYS[$name] ?? $name;
            $defaults[$key] = self::normalizeValue($key, $meta['default'] ?? '');
        }
        foreach (($registry['drivers'] ?? []) as $driver => $config) {
            $defaults['drivers'][$driver] = [];
            foreach (($config['config'] ?? []) as $name => $meta) {
                $defaults['drivers'][$driver][$name] = self::normalizeValue($name, $meta['default'] ?? '');
            }
        }

        return $defaults;
    }

    public static function payload(): array
    {
        return self::normalizePayload(self::readPayload());
    }

    public static function viewData(): array
    {
        $payload = self::payload();
        $payload['allowed_extensions_text'] = join(',', $payload['allowed_extensions']);
        return $payload;
    }

    public static function save(array $payload): bool
    {
        return self::writePayload(self::normalizePayload($payload));
    }

    private static function readPayload(): array
    {
        try {
            $payload = function_exists('sysget') ? sysget(self::DATA_KEY, []) : [];
            return is_array($payload) ? $payload : [];
        } catch (\Throwable) {
            return [];
        }
    }

    private static function writePayload(array $payload): bool
    {
        try {
            return function_exists('sysdata') ? boolval(sysdata(self::DATA_KEY, $payload)) : false;
        } catch (\Throwable) {
            return false;
        }
    }

    private static function normalizePayload(array $payload): array
    {
        $defaults = self::defaults();
        $data = array_replace_recursive($defaults, $payload);
        $registry = self::registry();
        $drivers = $registry['drivers'] ?? [];

        $data['default_driver'] = strtolower(strval($data['default_driver'] ?? ''));
        if ($data['default_driver'] === '' || !isset($drivers[$data['default_driver']])) {
            $data['default_driver'] = strval($registry['default'] ?? array_key_first($drivers) ?? 'local');
        }

        $data['naming_rule'] = strval($data['naming_rule'] ?? 'xmd5') ?: 'xmd5';
        $data['link_mode'] = strval($data['link_mode'] ?? 'none') ?: 'none';
        $data['allowed_extensions'] = self::normalizeValue('allowed_extensions', $data['allowed_extensions'] ?? []);

        $normalizedDrivers = [];
        foreach ($drivers as $driver => $meta) {
            $normalizedDrivers[$driver] = [];
            $current = is_array($data['drivers'][$driver] ?? null) ? $data['drivers'][$driver] : [];
            foreach (($meta['config'] ?? []) as $name => $item) {
                $normalizedDrivers[$driver][$name] = self::normalizeValue($name, $current[$name] ?? ($item['default'] ?? ''));
            }
        }

        $data['drivers'] = $normalizedDrivers;
        return $data;
    }

    private static function normalizeValue(string $name, mixed $value): mixed
    {
        if ($name === 'allowed_extensions') {
            if (is_string($value)) {
                $value = str2arr(strtolower($value));
            }
            $items = [];
            foreach ((array)$value as $item) {
                $item = strtolower(trim((string)$item));
                if ($item !== '' && !in_array($item, $items, true)) {
                    $items[] = $item;
                }
            }
            return $items;
        }

        return is_scalar($value) || $value === null ? trim((string)$value) : '';
    }
}
