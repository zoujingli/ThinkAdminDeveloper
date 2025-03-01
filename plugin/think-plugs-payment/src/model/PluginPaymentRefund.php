<?php

// +----------------------------------------------------------------------
// | Payment Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-payment
// | github 代码仓库：https://github.com/zoujingli/think-plugs-payment
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\payment\model;

use plugin\account\model\Abs;
use plugin\account\model\PluginAccountUser;
use think\model\relation\HasOne;

/**
 * 用户支付退款模型
 *
 * @property float $refund_amount 退款金额
 * @property float $used_balance 退回余额
 * @property float $used_integral 退回积分
 * @property float $used_payment 退回金额
 * @property int $id
 * @property int $refund_status 支付状态(0未付,1已付,2取消)
 * @property int $unid 主账号编号
 * @property int $usid 子账号编号
 * @property string $code 发起支付号
 * @property string $create_time 创建时间
 * @property string $record_code 子支付编号
 * @property string $refund_account 退回账号
 * @property string $refund_notify 通知内容
 * @property string $refund_remark 退款备注
 * @property string $refund_scode 状态编码
 * @property string $refund_time 完成时间
 * @property string $refund_trade 交易编号
 * @property string $update_time 更新时间
 * @property-read \plugin\account\model\PluginAccountUser $user
 * @property-read \plugin\payment\model\PluginPaymentRecord $record
 * @class PluginPaymentRecord
 * @package plugin\payment\model
 */
class PluginPaymentRefund extends Abs
{
    /**
     * 关联用户数据
     * @return \think\model\relation\HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(PluginAccountUser::class, 'id', 'unid');
    }

    /**
     * 关联子支付订单
     * @return \think\model\relation\HasOne
     */
    public function record(): HasOne
    {
        return $this->hasOne(PluginPaymentRecord::class, 'code', 'record_code');
    }

    /**
     * 格式化输出时间
     * @param mixed $value
     * @return string
     */
    public function getRefundTimeAttr($value): string
    {
        return format_datetime($value);
    }

    /**
     * 格式化输入时间
     * @param mixed $value
     * @return string
     */
    public function setRefundTimeAttr($value): string
    {
        return $this->setCreateTimeAttr($value);
    }
}