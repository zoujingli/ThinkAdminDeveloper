<?php

// +----------------------------------------------------------------------
// | Wuma Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 收费插件 ( https://thinkadmin.top/fee-introduce.html )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wuma
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wuma
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wuma\model;

use think\admin\Model;

/**
 * 抽象基础模型
 * @class AbstractPrivate
 * @package plugin\wuma\model
 */
abstract class AbstractPrivate extends Model
{
    /**
     * 格式化输出时间格式
     * @param mixed $value
     * @return string
     */
    public function getCreateTimeAttr($value): string
    {
        return $value ? format_datetime($value) : '';
    }

    /**
     * 格式化输出时间格式
     * @param mixed $value
     * @return string
     */
    public function getUpdateTimeAttr($value): string
    {
        return $value ? format_datetime($value) : '';
    }
}