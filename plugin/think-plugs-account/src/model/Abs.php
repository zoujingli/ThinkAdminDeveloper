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

namespace plugin\account\model;

use think\admin\Model;

/**
 * 模型抽象类.
 * @class Abs
 */
abstract class Abs extends Model
{
    /**
     * 格式化输出时间.
     * @param mixed $value
     */
    public function getCreateTimeAttr($value): string
    {
        return format_datetime($value);
    }

    /**
     * 格式化输出时间.
     * @param mixed $value
     */
    public function getUpdateTimeAttr($value): string
    {
        return format_datetime($value);
    }

    /**
     * 时间写入格式化.
     * @param mixed $value
     */
    public function setCreateTimeAttr($value): string
    {
        return is_string($value) ? str_replace(['年', '月', '日'], ['-', '-', ''], $value) : $value;
    }

    /**
     * 时间写入格式化.
     * @param mixed $value
     */
    public function setUpdateTimeAttr($value): string
    {
        return $this->setCreateTimeAttr($value);
    }

    /**
     * 字段属性处理.
     * @param mixed $value
     */
    public function setExtraAttr($value): string
    {
        return is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 字段属性处理.
     * @param mixed $value
     */
    public function getExtraAttr($value): array
    {
        return empty($value) ? [] : (is_string($value) ? json_decode($value, true) : $value);
    }
}
