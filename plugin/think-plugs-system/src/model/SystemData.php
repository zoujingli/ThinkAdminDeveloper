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

class SystemData extends Model
{
    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    /**
     * 获取数据内容.
     */
    public function getValueAttr(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && $value !== '') {
            $data = json_decode($value, true);
            if (is_array($data)) {
                return $data;
            }
        }
        return [];
    }

    /**
     * 设置数据内容.
     */
    public function setValueAttr(mixed $value): string
    {
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            }
        } elseif (is_object($value)) {
            $value = json_decode(json_encode($value, self::JSON_FLAGS), true) ?: [];
        }

        return json_encode(is_array($value) ? $value : [], self::JSON_FLAGS);
    }

    /**
     * 获取数据内容.
     */
    public function toString(): string
    {
        return json_encode($this->getAttr('value'), self::JSON_FLAGS);
    }
}
