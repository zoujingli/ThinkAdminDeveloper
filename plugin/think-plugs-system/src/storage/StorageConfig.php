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

/**
 * 存储业务配置：驱动注册表来自 `config('storage')`，实例级参数持久化在 sysdata(`system.storage`)。
 * @class StorageConfig
 */
class StorageConfig
{
    /** @var string sysdata 中存储参数的键名 */
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

    /**
     * 存储驱动注册表（对应框架已加载的 `storage` 配置，即 config/storage.php）。
     */
    public static function registry(): array
    {
        if (!function_exists('config')) {
            return [];
        }

        return (array)config('storage', []);
    }

    /**
     * 首次或结构变更时，将已保存参数与注册表默认结构对齐并写回。
     */
    public static function initialize(): void
    {
        $current = self::readPayload();
        $payload = self::normalizePayload($current);
        if ($payload !== $current) {
            self::writePayload($payload);
        }
    }

    /**
     * 读取全局项（当前驱动、命名规则、外链模式、允许后缀等）。
     */
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

    /**
     * 读取指定驱动下某一配置项的当前值。
     */
    public static function driver(string $driver, string $name, mixed $default = null): mixed
    {
        $payload = self::payload();
        return $payload['drivers'][$driver][$name] ?? null ?: $default;
    }

    /**
     * 由注册表生成默认 payload 结构（未与 sysdata 合并前）。
     */
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

    /**
     * 归一化后的已保存存储参数（合并默认值与 sysdata）。
     */
    public static function payload(): array
    {
        return self::normalizePayload(self::readPayload());
    }

    /**
     * 后台「存储配置」表单展示用数据（含允许后缀文案字段等）。
     */
    public static function viewData(): array
    {
        $payload = self::payload();
        $payload['allowed_extensions_text'] = join(',', $payload['allowed_extensions']);
        return $payload;
    }

    /**
     * 保存用户提交的存储参数到 sysdata。
     */
    public static function save(array $payload): bool
    {
        return self::writePayload(self::normalizePayload($payload));
    }

    /** @return array<string, mixed> */
    private static function readPayload(): array
    {
        try {
            $payload = function_exists('sysget') ? sysget(self::DATA_KEY, []) : [];
            return is_array($payload) ? $payload : [];
        } catch (\Throwable) {
            return [];
        }
    }

    /** 写入 sysdata */
    private static function writePayload(array $payload): bool
    {
        try {
            return function_exists('sysdata') ? boolval(sysdata(self::DATA_KEY, $payload)) : false;
        } catch (\Throwable) {
            return false;
        }
    }

    /** 合并默认值、校验驱动与字段类型 */
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

    /** 单字段清洗（后缀列表、标量 trim 等） */
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
