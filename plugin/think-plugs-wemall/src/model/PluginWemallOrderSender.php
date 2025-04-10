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

use think\model\relation\HasOne;

/**
 * 商城订单发货模型
 *
 * @property float $delivery_amount 配送计算金额
 * @property int $deleted 删除状态(0未删,1已删)
 * @property int $delivery_count 快递计费基数
 * @property int $id
 * @property int $ssid 所属商家
 * @property int $status 发货状态(1待发货,2已发货,3已收货)
 * @property int $unid 商城用户编号
 * @property string $address_id 配送地址编号
 * @property string $company_code 快递公司编码
 * @property string $company_name 快递公司名称
 * @property string $create_time 创建时间
 * @property string $delivery_code 配送模板编号
 * @property string $delivery_remark 配送计算描述
 * @property string $express_code 快递运送单号
 * @property string $express_remark 快递发送备注
 * @property string $express_time 快递发送时间
 * @property string $extra 原始数据
 * @property string $order_no 商城订单单号
 * @property string $region_addr 配送的详细地址
 * @property string $region_area 配送地址的区域
 * @property string $region_city 配送地址的城市
 * @property string $region_prov 配送地址的省份
 * @property string $update_time 更新时间
 * @property string $user_idcode 收货人证件号码
 * @property string $user_idimg1 收货人证件正面
 * @property string $user_idimg2 收货人证件反面
 * @property string $user_name 收货人联系名称
 * @property string $user_phone 收货人联系手机
 * @property-read \plugin\wemall\model\PluginWemallOrder $main
 * @class PluginWemallOrderSender
 * @package plugin\wemall\model
 */
class PluginWemallOrderSender extends AbsUser
{
    /**
     * 关联订单数据
     * @return \think\model\relation\HasOne
     */
    public function main(): HasOne
    {
        return $this->hasOne(PluginWemallOrder::class, 'order_no', 'order_no')->with(['items']);
    }

    /**
     * 设置发货时间
     * @param mixed $value
     * @return string
     */
    public function setExpressTimeAttr($value): string
    {
        return $this->setCreateTimeAttr($value);
    }

    /**
     * 获取发货时间
     * @param mixed $value
     * @return string
     */
    public function getExpressTimeAttr($value): string
    {
        return $this->getCreateTimeAttr($value);
    }
}