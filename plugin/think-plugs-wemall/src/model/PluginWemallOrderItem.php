<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
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

use think\model\relation\HasOne;

/**
 * 商城订单详情模型
 *
 * @property float $amount_cost 商品成本单价
 * @property float $discount_amount 商品优惠金额
 * @property float $discount_rate 销售价格折扣
 * @property float $price_market 商品市场单价
 * @property float $price_selling 商品销售单价
 * @property float $rebate_amount 参与返利金额
 * @property float $total_allow_balance 最大余额支付
 * @property float $total_allow_integral 最大兑换总分
 * @property float $total_price_cost 商品成本总价
 * @property float $total_price_market 商品市场总价
 * @property float $total_price_selling 商品销售总价
 * @property float $total_reward_balance 商品奖励余额
 * @property float $total_reward_integral 商品奖励积分
 * @property int $deleted 删除状态(0未删,1已删)
 * @property int $delivery_count 快递计费基数
 * @property int $discount_id 优惠方案编号
 * @property int $id
 * @property int $level_agent 推广权益(0无,1有)
 * @property int $level_code 用户等级序号
 * @property int $level_upgrade 购买升级等级(-1非入会,0不升级,其他升级)
 * @property int $rebate_type 参与返利状态(0不返,1返利)
 * @property int $ssid 所属商家
 * @property int $status 商品状态(1使用,0禁用)
 * @property int $stock_sales 包含商品数量
 * @property int $unid 用户编号
 * @property string $create_time 创建时间
 * @property string $delivery_code 快递邮费模板
 * @property string $gcode 商品编号
 * @property string $gcover 商品封面
 * @property string $ghash 商品哈希
 * @property string $gname 商品名称
 * @property string $gsku 商品SKU
 * @property string $gspec 商品规格
 * @property string $gunit 商品单凭
 * @property string $level_name 用户等级名称
 * @property string $order_no 订单单号
 * @property string $update_time 更新时间
 * @property-read \plugin\wemall\model\PluginWemallGoods $goods
 * @property-read \plugin\wemall\model\PluginWemallOrder $main
 * @class PluginWemallOrderItem
 * @package plugin\wemall\model
 */
class PluginWemallOrderItem extends AbsUser
{

    /**
     * 关联订单信息
     * @return \think\model\relation\HasOne
     */
    public function main(): HasOne
    {
        return $this->hasOne(PluginWemallOrder::class, 'order_no', 'order_no');
    }

    /**
     * 关联商品信息
     * @return \think\model\relation\HasOne
     */
    public function goods(): HasOne
    {
        return $this->hasOne(PluginWemallGoods::class, 'code', 'gcode');
    }
}