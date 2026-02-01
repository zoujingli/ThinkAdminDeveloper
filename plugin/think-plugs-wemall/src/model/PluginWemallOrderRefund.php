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

use plugin\wemall\service\UserRefund;
use think\model\relation\HasOne;

/**
 * 商品订单售后模型.
 *
 * @property float $amount 申请金额
 * @property float $balance_amount 退款余额
 * @property float $integral_amount 退款积分
 * @property float $payment_amount 退款支付
 * @property int $admin_by 后台用户
 * @property int $id
 * @property int $number 退货数量
 * @property int $ssid 所属商家
 * @property int $status 流程状态(0已取消,1预订单,2待审核,3待退货,4已退货,5待退款,6已退款,7已完成)
 * @property int $type 申请类型(1退货退款,2仅退款)
 * @property int $unid 用户编号
 * @property string $balance_code 退回单号
 * @property string $code 售后单号
 * @property string $content 申请说明
 * @property string $create_time 创建时间
 * @property string $express_code 快递公司
 * @property string $express_name 快递名称
 * @property string $express_no 快递单号
 * @property string $images 申请图片
 * @property string $integral_code 退回单号
 * @property string $order_no 订单单号
 * @property string $payment_code 退款单号
 * @property string $phone 联系电话
 * @property string $reason 退款原因
 * @property string $remark 操作描述
 * @property string $status_at 状态变更时间
 * @property string $status_ds 状态变更描述
 * @property string $update_time 更新时间
 * @property PluginWemallOrder $orderinfo
 * @class PluginWemallOrderRefund
 */
class PluginWemallOrderRefund extends AbsUser
{
    /**
     * 获取订单信息.
     */
    public function orderinfo(): HasOne
    {
        return $this->hasOne(PluginWemallOrder::class, 'order_no', 'order_no');
    }

    /**
     * 格式化售后图片.
     * @param mixed $value
     */
    public function getImagesAttr($value): array
    {
        return is_string($value) ? str2arr($value, '|') : [];
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        if (isset($data['type'])) {
            $data['typename'] = UserRefund::types[$data['type']] ?? $data['type'];
        }
        if (isset($data['reason'])) {
            $data['reasonname'] = UserRefund::reasons[$data['reason']] ?? $data['reason'];
        }
        return $data;
    }
}
