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

namespace plugin\wemall\model;

use think\model\relation\HasOne;

/**
 * 用户访问行为数据.
 *
 * @property int $id
 * @property int $sort 排序权重
 * @property int $ssid 所属商家
 * @property int $times 记录次数
 * @property int $unid 用户编号
 * @property string $create_time 创建时间
 * @property string $gcode 商品编号
 * @property string $update_time 更新时间
 * @property PluginWemallGoods $goods
 * @class PluginWemallUserActionHistory
 */
class PluginWemallUserActionHistory extends AbsUser
{
    /**
     * 关联商品信息.
     */
    public function goods(): HasOne
    {
        return $this->hasOne(PluginWemallGoods::class, 'code', 'gcode');
    }
}
