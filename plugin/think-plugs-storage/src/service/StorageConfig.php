<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | 版权所有 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | 官方网站: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | 开源协议 ( https://mit-license.org )
 * | 免责声明 ( https://thinkadmin.top/disclaimer )
 * | 会员特权 ( https://thinkadmin.top/vip-introduce )
 * +----------------------------------------------------------------------
 * | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
 * | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
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
        foreach (static::registry()['global'] ?? [] as $meta) {
            static::hydrate($meta);
        }
        foreach (static::registry()['drivers'] ?? [] as $driver) {
            foreach ($driver['config'] ?? [] as $meta) {
                static::hydrate($meta);
            }
        }
    }

    public static function global(string $name, mixed $default = null): mixed
    {
        $meta = static::registry()['global'][$name] ?? [];
        return static::resolve($meta, $default);
    }

    public static function driver(string $driver, string $name, mixed $default = null): mixed
    {
        $meta = static::registry()['drivers'][$driver]['config'][$name] ?? [];
        return static::resolve($meta, $default);
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
        $current = static::read($key);
        if (!static::missing($current)) {
            return;
        }
        foreach ((array)($meta['legacy'] ?? []) as $legacy) {
            $legacyValue = static::read((string)$legacy);
            if (!static::missing($legacyValue)) {
                static::write($key, $legacyValue);
                return;
            }
        }
        if (array_key_exists('default', $meta) && !static::missing($meta['default'])) {
            static::write($key, $meta['default']);
        }
    }

    private static function resolve(array $meta, mixed $default = null): mixed
    {
        $key = $meta['key'] ?? '';
        if (is_string($key) && $key !== '') {
            $value = static::read($key);
            if (!static::missing($value)) {
                return $value;
            }
        }
        foreach ((array)($meta['legacy'] ?? []) as $legacy) {
            $value = static::read((string)$legacy);
            if (!static::missing($value)) {
                return $value;
            }
        }
        if (array_key_exists('default', $meta) && !static::missing($meta['default'])) {
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
