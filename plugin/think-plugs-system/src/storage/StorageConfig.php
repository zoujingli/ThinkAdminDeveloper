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
 * 存储业务配置：驱动注册表内置于 registryDefinition()，实例级参数持久化在 sysdata(`system.storage`)。
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
     * 存储驱动注册表（内置定义，扩展驱动时在此维护）。
     */
    public static function registry(): array
    {
        return self::registryDefinition();
    }

    /**
     * 内置驱动元数据：default、global 键映射、各 driver 的 class/template/authorize/config 结构。
     */
    private static function registryDefinition(): array
    {
        return [
            'default' => 'local',
            'global' => [
                'driver' => ['key' => 'storage.driver', 'legacy' => ['storage.type'], 'default' => 'local'],
                'naming' => ['key' => 'storage.naming', 'legacy' => ['storage.name_type'], 'default' => 'xmd5'],
                'link' => ['key' => 'storage.link', 'legacy' => ['storage.link_type'], 'default' => 'none'],
                'allowed_exts' => ['key' => 'storage.allowed_exts', 'legacy' => ['storage.allow_exts'], 'default' => 'doc,gif,ico,jpg,mp3,mp4,p12,pem,png,rar,xls,xlsx'],
            ],
            'drivers' => [
                'local' => [
                    'label' => '本地服务器存储',
                    'class' => LocalStorage::class,
                    'template' => 'storage-local',
                    'regions' => [LocalStorage::class, 'region'],
                    'authorize' => [StorageAuthorize::class, 'local'],
                    'config' => [
                        'protocol' => ['key' => 'storage.local.protocol', 'legacy' => ['storage.local_http_protocol'], 'default' => 'follow'],
                        'domain' => ['key' => 'storage.local.domain', 'legacy' => ['storage.local_http_domain'], 'default' => ''],
                    ],
                ],
                'alist' => [
                    'label' => '自建Alist存储',
                    'class' => AlistStorage::class,
                    'template' => 'storage-alist',
                    'regions' => [AlistStorage::class, 'region'],
                    'authorize' => [StorageAuthorize::class, 'alist'],
                    'config' => [
                        'protocol' => ['key' => 'storage.alist.protocol', 'legacy' => ['storage.alist_http_protocol'], 'default' => 'http'],
                        'domain' => ['key' => 'storage.alist.domain', 'legacy' => ['storage.alist_http_domain'], 'default' => ''],
                        'path' => ['key' => 'storage.alist.path', 'legacy' => ['storage.alist_savepath'], 'default' => ''],
                        'username' => ['key' => 'storage.alist.username', 'legacy' => ['storage.alist_username'], 'default' => ''],
                        'password' => ['key' => 'storage.alist.password', 'legacy' => ['storage.alist_password'], 'default' => ''],
                    ],
                ],
                'qiniu' => [
                    'label' => '七牛云对象存储',
                    'class' => QiniuStorage::class,
                    'template' => 'storage-qiniu',
                    'regions' => [QiniuStorage::class, 'region'],
                    'authorize' => [StorageAuthorize::class, 'qiniu'],
                    'config' => [
                        'protocol' => ['key' => 'storage.qiniu.protocol', 'legacy' => ['storage.qiniu_http_protocol'], 'default' => 'http'],
                        'region' => ['key' => 'storage.qiniu.region', 'legacy' => ['storage.qiniu_region'], 'default' => ''],
                        'bucket' => ['key' => 'storage.qiniu.bucket', 'legacy' => ['storage.qiniu_bucket'], 'default' => ''],
                        'domain' => ['key' => 'storage.qiniu.domain', 'legacy' => ['storage.qiniu_http_domain', 'storage.qiniu_domain'], 'default' => ''],
                        'access_key' => ['key' => 'storage.qiniu.access_key', 'legacy' => ['storage.qiniu_access_key'], 'default' => ''],
                        'secret_key' => ['key' => 'storage.qiniu.secret_key', 'legacy' => ['storage.qiniu_secret_key'], 'default' => ''],
                    ],
                ],
                'upyun' => [
                    'label' => '又拍云USS存储',
                    'class' => UpyunStorage::class,
                    'template' => 'storage-upyun',
                    'regions' => [UpyunStorage::class, 'region'],
                    'authorize' => [StorageAuthorize::class, 'upyun'],
                    'config' => [
                        'protocol' => ['key' => 'storage.upyun.protocol', 'legacy' => ['storage.upyun_http_protocol'], 'default' => 'http'],
                        'bucket' => ['key' => 'storage.upyun.bucket', 'legacy' => ['storage.upyun_bucket'], 'default' => ''],
                        'domain' => ['key' => 'storage.upyun.domain', 'legacy' => ['storage.upyun_http_domain'], 'default' => ''],
                        'username' => ['key' => 'storage.upyun.username', 'legacy' => ['storage.upyun_access_key'], 'default' => ''],
                        'password' => ['key' => 'storage.upyun.password', 'legacy' => ['storage.upyun_secret_key'], 'default' => ''],
                    ],
                ],
                'txcos' => [
                    'label' => '腾讯云COS存储',
                    'class' => TxcosStorage::class,
                    'template' => 'storage-txcos',
                    'regions' => [TxcosStorage::class, 'region'],
                    'authorize' => [StorageAuthorize::class, 'txcos'],
                    'config' => [
                        'protocol' => ['key' => 'storage.txcos.protocol', 'legacy' => ['storage.txcos_http_protocol'], 'default' => 'http'],
                        'region' => ['key' => 'storage.txcos.region', 'legacy' => ['storage.txcos_point'], 'default' => ''],
                        'bucket' => ['key' => 'storage.txcos.bucket', 'legacy' => ['storage.txcos_bucket'], 'default' => ''],
                        'domain' => ['key' => 'storage.txcos.domain', 'legacy' => ['storage.txcos_http_domain'], 'default' => ''],
                        'access_key' => ['key' => 'storage.txcos.access_key', 'legacy' => ['storage.txcos_access_key'], 'default' => ''],
                        'secret_key' => ['key' => 'storage.txcos.secret_key', 'legacy' => ['storage.txcos_secret_key'], 'default' => ''],
                    ],
                ],
                'alioss' => [
                    'label' => '阿里云OSS存储',
                    'class' => AliossStorage::class,
                    'template' => 'storage-alioss',
                    'regions' => [AliossStorage::class, 'region'],
                    'authorize' => [StorageAuthorize::class, 'alioss'],
                    'config' => [
                        'protocol' => ['key' => 'storage.alioss.protocol', 'legacy' => ['storage.alioss_http_protocol'], 'default' => 'http'],
                        'region' => ['key' => 'storage.alioss.region', 'legacy' => ['storage.alioss_point'], 'default' => ''],
                        'bucket' => ['key' => 'storage.alioss.bucket', 'legacy' => ['storage.alioss_bucket'], 'default' => ''],
                        'domain' => ['key' => 'storage.alioss.domain', 'legacy' => ['storage.alioss_http_domain'], 'default' => ''],
                        'access_key' => ['key' => 'storage.alioss.access_key', 'legacy' => ['storage.alioss_access_key'], 'default' => ''],
                        'secret_key' => ['key' => 'storage.alioss.secret_key', 'legacy' => ['storage.alioss_secret_key'], 'default' => ''],
                    ],
                ],
            ],
        ];
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
