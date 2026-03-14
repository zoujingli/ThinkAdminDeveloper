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

namespace plugin\worker\model;

use think\admin\Model;

class SystemQueue extends Model
{
    protected $updateTime = false;

    public function getExecTimeAttr($value): string
    {
        return format_datetime($value);
    }

    public function getEnterTimeAttr($value): string
    {
        return bccomp(strval($value), '0.00', 2) > 0 ? format_datetime((int)$value) : '';
    }

    public function getOuterTimeAttr($value, array $data): string
    {
        if ($value > 0 && $value > $data['enter_time']) {
            return lang('耗时 %.4f 秒', [$data['outer_time'] - $data['enter_time']]);
        }

        return ' - ';
    }

    public function getCreateTimeAttr($value): string
    {
        return format_datetime($value);
    }
}
