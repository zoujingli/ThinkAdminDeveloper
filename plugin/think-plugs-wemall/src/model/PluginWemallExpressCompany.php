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

namespace plugin\wemall\model;

use plugin\account\model\Abs;

/**
 * 商城快递公司数据.
 *
 * @property int $deleted 删除状态(1已删,0未删)
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
        $map = ['status' => 1, 'deleted' => 0];
        return self::mk()->where($map)->order('sort desc,id desc')->column('name', 'code');
    }
}
