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
use plugin\account\model\PluginAccountUser;
use think\model\relation\HasOne;

/**
 * 用户支付退款模型.
 *
 * @property string $refund_amount 退款金额
 * @property string $used_balance 退回余额
 * @property string $used_integral 退回积分
 * @property string $used_payment 退回金额
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
 * @property PluginAccountUser $user
 * @property PluginPaymentRecord $record
 * @class PluginPaymentRecord
 */
class PluginPaymentRefund extends Abs
{
    /**
     * 关联用户数据.
     */
    public function user(): HasOne
    {
        return $this->hasOne(PluginAccountUser::class, 'id', 'unid');
    }

    /**
     * 关联子支付订单.
     */
    public function record(): HasOne
    {
        return $this->hasOne(PluginPaymentRecord::class, 'code', 'record_code');
    }

    /**
     * 格式化输出时间.
     * @param mixed $value
     */
    public function getRefundTimeAttr($value): string
    {
        return format_datetime($value);
    }

    /**
     * 格式化输入时间.
     * @param mixed $value
     */
    public function setRefundTimeAttr($value): string
    {
        return $this->setCreateTimeAttr($value);
    }
}
