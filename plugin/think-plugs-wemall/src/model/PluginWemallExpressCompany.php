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

namespace plugin\wemall\model;

use plugin\account\model\Abs;

/**
 * 商城快递公司数据.
 *
 * @property string $delete_time 删除时间
 * @property int $id
 * @property int $sort 排序权重
 * @property int $status 激活状态(0无效,1有效)
 * @property string $code 公司代码
 * @property string $create_time 创建时间
 * @property string $name 公司名称
 * @property string $remark 公司描述
 * @property string $update_time 更新时间
 * @class PluginWemallExpressCompany
 */
class PluginWemallExpressCompany extends Abs
{
    /**
     * 获取快递公司数据.
     */
    public static function items(): array
    {
        $map = ['status' => 1];
        return self::mk()->where($map)->order('sort desc,id desc')->column('name', 'code');
    }
}
