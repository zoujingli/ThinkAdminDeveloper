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

namespace plugin\storage\model;

use think\admin\Model;

/**
 * 存储文件模型.
 * 文件记录归属存储域，不再绑定后台用户关系。
 * @class SystemFile
 */
class SystemFile extends Model
{
    /**
     * 格式化创建时间.
     * @param mixed $value
     */
    public function getCreateTimeAttr($value): string
    {
        return format_datetime($value);
    }

    /**
     * 格式化更新时间.
     * @param mixed $value
     */
    public function getUpdateTimeAttr($value): string
    {
        return format_datetime($value);
    }
}
