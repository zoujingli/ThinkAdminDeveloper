<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wemall
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wemall
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wemall\model;

use plugin\account\model\Abs;
use think\model\relation\HasOne;

/**
 * 商城商品规格数据
 *
 * @property float $allow_balance 余额支付
 * @property float $allow_integral 兑换积分
 * @property float $price_cost 进货成本
 * @property float $price_market 市场价格
 * @property float $price_selling 销售价格
 * @property float $reward_balance 奖励余额
 * @property float $reward_integral 奖励积分
 * @property int $id
 * @property int $number_express 计件系数
 * @property int $number_virtual 虚拟销量
 * @property int $status 商品状态
 * @property int $stock_sales 销售数量
 * @property int $stock_total 商品库存
 * @property string $create_time 创建时间
 * @property string $gcode 商品编号
 * @property string $ghash 商品哈希
 * @property string $gimage 商品图片
 * @property string $gsku 商品SKU
 * @property string $gspec 商品规格
 * @property string $gunit 商品单位
 * @property string $update_time 更新时间
 * @property-read \plugin\wemall\model\PluginWemallGoods $bind_goods
 * @property-read \plugin\wemall\model\PluginWemallGoods $goods
 * @class PluginWemallGoodsItem
 * @package plugin\wemall\model
 */
class PluginWemallGoodsItem extends Abs
{

    /**
     * 关联商品信息
     * @return \think\model\relation\HasOne
     */
    public function goods(): HasOne
    {
        return $this->hasOne(PluginWemallGoods::class, 'code', 'gcode');
    }

    /**
     * 绑定商品信息
     * @return \think\model\relation\HasOne
     */
    public function bindGoods(): HasOne
    {
        return $this->goods()->bind([
            'gname'    => 'name',
            'gcover'   => 'cover',
            'gstatus'  => 'status',
            'gdeleted' => 'deleted'
        ]);
    }

    /**
     * 获取商品规格JSON数据
     * @param string $code
     * @return string
     */
    public static function itemsJson(string $code): string
    {
        return json_encode(self::itemsArray($code), 64 | 256);
    }

    /**
     * 获取商品规格数组
     * @param string $code
     * @return array
     */
    public static function itemsArray(string $code): array
    {
        return self::mk()->where(['gcode' => $code])->column([
            'gsku'            => 'gsku',
            'ghash'           => 'hash',
            'gspec'           => 'spec',
            'gcode'           => 'gcode',
            'gimage'          => 'image',
            'status'          => 'status',
            'price_cost'      => 'cost',
            'price_market'    => 'market',
            'price_selling'   => 'selling',
            'allow_balance'   => 'allow_balance',
            'allow_integral'  => 'allow_integral',
            'number_virtual'  => 'virtual',
            'number_express'  => 'express',
            'reward_balance'  => 'balance',
            'reward_integral' => 'integral',
        ], 'ghash');
    }
}