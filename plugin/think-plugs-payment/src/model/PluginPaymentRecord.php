<?php

// +----------------------------------------------------------------------
// | Payment Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-payment
// | github 代码仓库：https://github.com/zoujingli/think-plugs-payment
// +----------------------------------------------------------------------

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

namespace plugin\payment\model;

use plugin\account\model\Abs;
use plugin\account\model\PluginAccountBind;
use plugin\account\model\PluginAccountUser;
use plugin\payment\service\Payment;
use think\model\relation\HasOne;

/**
 * 用户支付行为模型.
 *
 * @property array $payment_notify 支付通知内容
 * @property string $order_amount 原订单金额
 * @property string $payment_amount 实际支付金额
 * @property string $payment_coupon 平台优惠券金额
 * @property string $refund_amount 累计退款
 * @property string $refund_balance 退回余额
 * @property string $refund_integral 退回积分
 * @property string $refund_payment 退回金额
 * @property string $used_balance 扣除余额
 * @property string $used_integral 扣除积分
 * @property string $used_payment 支付金额
 * @property int $audit_status 审核状态(0已拒,1待审,2已审)
 * @property int $audit_user 审核用户(系统用户ID)
 * @property int $id
 * @property int $payment_status 支付状态(0未付,1已付,2取消)
 * @property int $refund_status 退款状态(0未退,1已退)
 * @property int $unid 主账号编号
 * @property int $usid 子账号编号
 * @property string $audit_remark 审核描述
 * @property string $audit_time 审核时间
 * @property string $channel_code 支付通道编号
 * @property string $channel_type 支付通道类型
 * @property string $code 发起支付号
 * @property string $create_time 创建时间
 * @property string $order_name 原订单标题
 * @property string $order_no 原订单编号
 * @property string $payment_images 凭证支付图片
 * @property string $payment_remark 支付状态备注
 * @property string $payment_time 支付生效时间
 * @property string $payment_trade 平台交易编号
 * @property string $update_time 更新时间
 * @property PluginAccountBind $device
 * @property array $user
 * @class PluginPaymentRecord
 */
class PluginPaymentRecord extends Abs
{
    /**
     * 关联用户数据.
     */
    public function user(): HasOne
    {
        return $this->hasOne(PluginAccountUser::class, 'id', 'unid');
    }

    /**
     * 关联客户端数据.
     */
    public function device(): HasOne
    {
        return $this->hasOne(PluginAccountBind::class, 'id', 'usid');
    }

    public function getUserAttr($value): array
    {
        return is_array($value) ? $value : [];
    }

    /**
     * 格式化时间.
     * @param mixed $value
     */
    public function getAuditTimeAttr($value): string
    {
        return $this->getCreateTimeAttr($value);
    }

    /**
     * 格式化时间.
     * @param mixed $value
     */
    public function setAuditTimeAttr($value): string
    {
        return $this->setCreateTimeAttr($value);
    }

    /**
     * 格式化输出时间.
     * @param mixed $value
     */
    public function getPaymentTimeAttr($value): string
    {
        return $this->getCreateTimeAttr($value);
    }

    /**
     * 格式化时间.
     * @param mixed $value
     */
    public function setPaymentTimeAttr($value): string
    {
        return $this->setCreateTimeAttr($value);
    }

    /**
     *  格式化通知.
     * @param mixed $value
     */
    public function setPaymentNotifyAttr($value): string
    {
        return $this->setExtraAttr($value);
    }

    /**
     * 格式化通知.
     * @param mixed $value
     */
    public function getPaymentNotifyAttr($value): array
    {
        return $this->getExtraAttr($value);
    }

    /**
     * 数据输出处理.
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        if (isset($data['channel_type'])) {
            $data['channel_type_name'] = Payment::typeName($data['channel_type']);
        }
        return $data;
    }
}
