<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | Payment Plugin for ThinkAdmin
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

namespace plugin\wuma\model;

use think\admin\Model;

/**
 * 抽象基础模型.
 * @class AbstractPrivate
 */
abstract class AbstractPrivate extends Model
{
    /**
     * 格式化输出时间格式.
     * @param mixed $value
     */
    public function getCreateTimeAttr($value): string
    {
        return $value ? format_datetime($value) : '';
    }

    /**
     * 格式化输出时间格式.
     * @param mixed $value
     */
    public function getUpdateTimeAttr($value): string
    {
        return $value ? format_datetime($value) : '';
    }
}
