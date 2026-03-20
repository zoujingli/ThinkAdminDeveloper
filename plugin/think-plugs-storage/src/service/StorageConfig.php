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

namespace plugin\storage\service;

class StorageConfig
{
    public static function registry(): array
    {
        static $registry = [];
        if ($registry !== []) {
            return $registry;
        }
        $file = dirname(__DIR__, 2) . '/stc/config/storage.php';
        return $registry = is_file($file) ? include $file : [];
    }

    public static function initialize(): void
    {
        foreach (self::registry()['global'] ?? [] as $meta) {
            self::hydrate($meta);
        }
        foreach (self::registry()['drivers'] ?? [] as $driver) {
            foreach ($driver['config'] ?? [] as $meta) {
                self::hydrate($meta);
            }
        }
    }

    public static function global(string $name, mixed $default = null): mixed
    {
        $meta = self::registry()['global'][$name] ?? [];
        return self::resolve($meta, $default);
    }

    public static function driver(string $driver, string $name, mixed $default = null): mixed
    {
        $meta = self::registry()['drivers'][$driver]['config'][$name] ?? [];
        return self::resolve($meta, $default);
    }

    public static function key(string $scope, string $name, ?string $driver = null): string
    {
        if ($scope === 'global') {
            return (string)((static::registry()['global'][$name]['key'] ?? '') ?: "storage.{$name}");
        }
        return (string)((static::registry()['drivers'][$driver]['config'][$name]['key'] ?? '') ?: "storage.{$driver}.{$name}");
    }

    public static function defaults(): array
    {
        $defaults = [];
        foreach (static::registry()['global'] ?? [] as $name => $meta) {
            $defaults[static::key('global', $name)] = $meta['default'] ?? '';
        }
        foreach (static::registry()['drivers'] ?? [] as $driver => $config) {
            foreach ($config['config'] ?? [] as $name => $meta) {
                $defaults[static::key('driver', $name, $driver)] = $meta['default'] ?? '';
            }
        }
        return $defaults;
    }

    private static function hydrate(array $meta): void
    {
        $key = $meta['key'] ?? '';
        if (!is_string($key) || $key === '') {
            return;
        }
        $current = self::read($key);
        if (!self::missing($current)) {
            return;
        }
        foreach ((array)($meta['legacy'] ?? []) as $legacy) {
            $legacyValue = self::read((string)$legacy);
            if (!self::missing($legacyValue)) {
                self::write($key, $legacyValue);
                return;
            }
        }
        if (array_key_exists('default', $meta) && !self::missing($meta['default'])) {
            self::write($key, $meta['default']);
        }
    }

    private static function resolve(array $meta, mixed $default = null): mixed
    {
        $key = $meta['key'] ?? '';
        if (is_string($key) && $key !== '') {
            $value = self::read($key);
            if (!self::missing($value)) {
                return $value;
            }
        }
        foreach ((array)($meta['legacy'] ?? []) as $legacy) {
            $value = self::read((string)$legacy);
            if (!self::missing($value)) {
                return $value;
            }
        }
        if (array_key_exists('default', $meta) && !self::missing($meta['default'])) {
            return $meta['default'];
        }
        return $default;
    }

    private static function read(string $name): mixed
    {
        try {
            return function_exists('sysconf') ? sysconf("{$name}|raw") : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function write(string $name, mixed $value): void
    {
        try {
            if (function_exists('sysconf')) {
                sysconf($name, $value);
            }
        } catch (\Throwable) {
        }
    }

    private static function missing(mixed $value): bool
    {
        return $value === null || $value === '';
    }
}
