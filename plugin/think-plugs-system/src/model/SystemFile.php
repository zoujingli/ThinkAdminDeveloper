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

namespace plugin\system\model;

use think\admin\Model;

/**
 * 附件元数据模型（对应 system_file 等表，依迁移定义）。
 *
 * @property string $extension 文件后缀
 * @property string $file_url 文件链接
 * @property string $storage_key 存储键名
 * @property int $system_user_id 后台用户
 * @property int $biz_user_id 业务用户
 * @property int $is_fast_upload 是否秒传
 * @property int $is_safe 安全模式
 * @class SystemFile
 */
class SystemFile extends Model
{
    /**
     * 同步新旧文件字段.
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function syncPayload(array $data): array
    {
        $maps = [
            ['extension', 'xext', 'string'],
            ['file_url', 'xurl', 'string'],
            ['storage_key', 'xkey', 'string'],
            ['system_user_id', 'uuid', 'int'],
            ['biz_user_id', 'unid', 'int'],
            ['is_fast_upload', 'isfast', 'int'],
            ['is_safe', 'issafe', 'int'],
        ];
        foreach ($maps as [$current, $legacy, $type]) {
            $hasCurrent = array_key_exists($current, $data);
            $hasLegacy = array_key_exists($legacy, $data);
            if (!$hasCurrent && !$hasLegacy) {
                continue;
            }
            $value = self::resolveAliasValue($data, $current, $legacy, $type);
            $data[$current] = self::normalizeAliasValue($value, $type, $current);
            $data[$legacy] = $data[$current];
        }

        return $data;
    }

    /**
     * 标准化文件数据行.
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function normalizeRow(array $data): array
    {
        return self::syncPayload($data);
    }

    /**
     * 获取已存在的后缀选项.
     * @return string[]
     */
    public static function distinctExtensions(): array
    {
        $items = array_merge(
            array_map('strval', static::mk()->distinct()->column('extension')),
            array_map('strval', static::mk()->distinct()->column('xext'))
        );
        $items = array_values(array_unique(array_filter(array_map(static function (string $item): string {
            return strtolower(trim($item));
        }, $items))));
        sort($items);

        return $items;
    }

    private static function normalizeAliasValue(mixed $value, string $type, string $field): int|string
    {
        if ($type === 'int') {
            return intval($value);
        }

        $value = trim(strval($value));
        if ($field === 'extension') {
            return strtolower($value);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function resolveAliasValue(array $data, string $current, string $legacy, string $type): mixed
    {
        $hasCurrent = array_key_exists($current, $data);
        $hasLegacy = array_key_exists($legacy, $data);
        if (!$hasCurrent) {
            return $data[$legacy] ?? null;
        }
        if (!$hasLegacy) {
            return $data[$current];
        }
        if ($type === 'int') {
            $currentValue = intval($data[$current]);
            $legacyValue = intval($data[$legacy]);
            return $currentValue === 0 && $legacyValue !== 0 ? $data[$legacy] : $data[$current];
        }

        $currentValue = trim(strval($data[$current]));
        $legacyValue = trim(strval($data[$legacy]));
        return $currentValue === '' && $legacyValue !== '' ? $data[$legacy] : $data[$current];
    }
}
