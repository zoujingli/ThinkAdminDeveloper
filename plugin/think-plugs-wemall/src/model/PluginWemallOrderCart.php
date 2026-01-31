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

use think\model\relation\HasOne;

/**
 * Class plugin\wemall\model\PluginWemallOrderCart.
 *
 * @property int $id
 * @property int $number 商品数量
 * @property int $ssid 所属商家
 * @property int $unid 用户编号
 * @property string $create_time 创建时间
 * @property string $gcode 商品编号
 * @property string $ghash 规格哈希
 * @property string $gspec 商品规格
 * @property string $update_time 更新时间
 * @property PluginWemallGoods $goods
 * @property PluginWemallGoodsItem $specs
 */
class PluginWemallOrderCart extends AbsUser
{
    /**
     * 关联产品数据.
     */
    public function goods(): HasOne
    {
        return $this->hasOne(PluginWemallGoods::class, 'code', 'gcode');
    }

    /**
     * 关联规格数据.
     */
    public function specs(): HasOne
    {
        return $this->hasOne(PluginWemallGoodsItem::class, 'ghash', 'ghash');
    }
}
