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

namespace plugin\payment\service\contract;

use plugin\account\service\contract\AccountInterface;
use plugin\payment\model\PluginPaymentRecord;
use plugin\payment\model\PluginPaymentRefund;
use plugin\payment\service\Payment;
use think\admin\Exception;
use think\admin\extend\CodeExtend;
use think\admin\Library;
use think\App;
use WeChat\Exceptions\InvalidResponseException;
use WeChat\Exceptions\LocalCacheException;

/**
 * 支付方式公共操作.
 * @class PaymentUsageTrait
 */
trait PaymentUsageTrait
{
    /**
     * 当前应用对象
     * @var App
     */
    protected $app;

    /**
     * 标准返回对象
     * @var PaymentResponse
     */
    protected $res;

    /**
     * 支付调度参数.
     * @var array
     */
    protected $config;

    /**
     * 支付方式编号.
     * @var string
     */
    protected $cfgCode;

    /**
     * 支付方式类型.
     * @var string
     */
    protected $cfgType;

    /**
     * 支付方式参数.
     * @var array
     */
    protected $cfgParams;

    /**
     * 支付方式构造函数.
     * @param string $code 配置编码
     * @param string $type 配置类型
     * @param array $params 配置参数
     * @throws InvalidResponseException
     * @throws LocalCacheException
     */
    public function __construct(App $app, string $code, string $type, array $params)
    {
        $this->app = $app;
        $this->cfgCode = $code;
        $this->cfgType = $type;
        $this->cfgParams = $params;
        // 初始化支付响应对象
        $this->res = new PaymentResponse();
        $this->res->channleCode = $code;
        $this->res->channelType = $type;
        $this->init();
    }

    /**
     * 获取支付参数.
     */
    public function config(): array
    {
        return array_merge($this->config, [
            'channel_type' => $this->cfgType,
            'channel_code' => $this->cfgCode,
        ]);
    }

    /**
     * 支付实例创建器.
     */
    public static function mk(string $code, string $type, array $params): PaymentInterface
    {
        /* @var \plugin\payment\service\contract\PaymentInterface */
        return app(static::class, ['code' => $code, 'type' => $type, 'params' => $params]);
    }

    /**
     * 初始化支付方式.
     */
    abstract public function init(): PaymentInterface;

    /**
     * 同步退款统计状态
     * @param string $pCode 支付单号
     * @param ?string $rCode 退款单号&引用
     * @param ?string $amount 退款金额 ( null 表示需要处理退款，仅同步数据 )
     * @param string $reason 退款原因
     * @throws Exception
     */
    public static function syncRefund(string $pCode, ?string &$rCode = '', ?string $amount = null, string $reason = ''): PluginPaymentRecord
    {
        // 检查退款单号
        if ($rCode && PluginPaymentRefund::mk()->where(['code' => $pCode])->findOrEmpty()->isExists()) {
            throw new Exception('退款单已存在！', 2);
        }
        // 查询支付记录
        $record = self::withPaymentByRefundTotal($pCode);
        if ($record->getAttr('payment_status') < 1) {
            throw new Exception('支付未完成！');
        }
        // 是否需要写入退款
        if (!is_numeric($amount)) {
            return $record->refresh();
        }
        // 生成退款记录
        $pType = $record->getAttr('channel_type');
        $extra = ['used_payment' => $amount, 'refund_status' => 0];
        if (in_array($pType, [Payment::EMPTY, Payment::COUPON, Payment::BALANCE, Payment::INTEGRAL, Payment::VOUCHER])) {
            if ($pType === Payment::BALANCE) {
                $extra['used_balance'] = $amount;
            } elseif ($pType === Payment::INTEGRAL) {
                $extra['used_integral'] = strval(bcdiv(bcmul(strval($amount), strval($record->getAttr('used_integral')), 6), strval($record->getAttr('payment_amount')), 2));
            }
            $extra['refund_trade'] = CodeExtend::uniqidNumber(16, 'RT');
            $extra['refund_account'] = $pType;
            $extra['refund_scode'] = 'SUCCESS';
            $extra['refund_status'] = 1;
            $extra['refund_time'] = date('Y-m-d H:i:s');
        }
        // 支付金额大于0，并需要创建退款记录
        $refundAmountFloat = strval($amount);
        $currentRefundAmount = strval($record->getAttr('refund_amount'));
        if (bccomp(bcadd($currentRefundAmount, $refundAmountFloat, 2), strval($record->getAttr('payment_amount')), 2) <= 0) {
            PluginPaymentRefund::mk()->save(array_merge([
                'unid' => $record->getAttr('unid'), 'record_code' => $pCode,
                'usid' => $record->getAttr('usid'), 'refund_amount' => $amount,
                'code' => $rCode = $rCode ?: Payment::withRefundCode(), 'refund_remark' => $reason,
            ], $extra));
            // 同步刷新金额
            self::withPaymentByRefundTotal($record);
        }
        // 更新模型数据
        $record->save();
        // 触发取消支付事件
        Library::$sapp->event->trigger('PluginPaymentCancel', $record->refresh());
        return $record;
    }

    /**
     * 检查订单支付金额.
     * @param string $orderNo
     * @param mixed $payAmount
     * @param mixed $orderAmount
     * @throws Exception
     */
    protected function checkLeaveAmount($orderNo, $payAmount, $orderAmount): string
    {
        // 检查未审核的记录
        $map = ['order_no' => $orderNo, 'audit_status' => 1];
        $model = PluginPaymentRecord::mk()->where($map)->findOrEmpty();
        if ($model->isExists()) {
            throw new Exception('凭证待审核！', 0);
        }
        // 检查支付金额是否超出
        $payAmountFloat = strval($payAmount);
        $orderAmountFloat = strval($orderAmount);
        $paidAmount = strval(Payment::paidAmount($orderNo, true));
        if (bccomp(bcadd($payAmountFloat, $paidAmount, 2), $orderAmountFloat, 2) > 0) {
            throw new Exception('支付金额溢出！');
        }
        return $payAmountFloat;
    }

    /**
     * 创建支付行为.
     * @param string $orderNo 订单单号
     * @param string $orderTitle 订单标题
     * @param string $orderAmount 订单总金额
     * @param string $payCode 此次支付单号
     * @param string $payAmount 此次支付金额
     * @param string $payImages 支付凭证图片
     * @param string $usedBalance 使用余额
     * @param string $usedIntegral 使用积分
     * @throws Exception
     */
    protected function createAction(string $orderNo, string $orderTitle, string $orderAmount, string $payCode, string $payAmount, string $payImages = '', string $usedBalance = '0.00', string $usedIntegral = '0.00'): array
    {
        // 检查是否已经支付
        $map = ['order_no' => $orderNo, 'payment_status' => 1];
        $total = strval(Payment::paidAmount($orderNo, true));
        $orderAmountFloat = strval($orderAmount);
        if (bccomp($total, $orderAmountFloat, 2) >= 0 && bccomp($orderAmountFloat, '0.00', 2) > 0) {
            throw new Exception('已经完成支付！', 1);
        }
        $payAmountFloat = strval($payAmount);
        if (bccomp(bcadd($total, $payAmountFloat, 2), $orderAmountFloat, 2) > 0) {
            throw new Exception('支付大于金额！', 0);
        }
        $map['code'] = $payCode;
        if (($model = PluginPaymentRecord::mk()->where($map)->findOrEmpty())->isExists()) {
            throw new Exception('已经完成支付2', 1);
        }
        // 写入订单支付行为
        $model->save([
            'unid' => intval(sysvar('PluginPaymentUnid')),
            'usid' => intval(sysvar('PluginPaymentUsid')),
            'code' => $payCode,
            'order_no' => $orderNo,
            'order_name' => $orderTitle,
            'order_amount' => $orderAmount,
            'channel_code' => $this->cfgCode,
            'channel_type' => $this->cfgType,
            'payment_amount' => $this->cfgType === Payment::VOUCHER ? $payAmount : 0.00,
            'payment_images' => $payImages,
            'audit_time' => date('Y-m-d H:i:s'),
            'audit_status' => $this->cfgType === Payment::VOUCHER ? 1 : 2,
            'used_payment' => $payAmount,
            'used_balance' => $usedBalance,
            'used_integral' => $usedIntegral,
        ]);

        // 触发支付审核事件
        $record = $model->refresh();
        if ($this->cfgType === Payment::VOUCHER) {
            $this->app->event->trigger('PluginPaymentAudit', $record);
        }
        return $record->toArray();
    }

    /**
     * 更新支付行为记录.
     * @param string $pCode 商户订单单号
     * @param string $pTrade 平台交易单号
     * @param string $pAmount 实际支付金额
     * @param null|string $pRemark 平台支付备注
     * @param null|string $pCoupon 优惠券金额
     * @param null|array $pNotify 支付通知数据
     * @return array|false
     */
    protected function updateAction(string $pCode, string $pTrade, string $pAmount, ?string $pRemark = '在线支付', ?string $pCoupon = null, ?array $pNotify = null)
    {
        // 更新支付记录
        $map = ['code' => $pCode, 'channel_code' => $this->cfgCode, 'channel_type' => $this->cfgType];
        if (($model = PluginPaymentRecord::mk()->where($map)->findOrEmpty())->isEmpty()) {
            return false;
        }
        $data = [
            'code' => $pCode,
            'channel_code' => $this->cfgCode,
            'channel_type' => $this->cfgType,
            'payment_time' => date('Y-m-d H:i:s'),
            'payment_trade' => $pTrade,
            'payment_status' => 1,
            'payment_amount' => $pAmount,
        ];
        if (is_array($pNotify)) {
            $data['payment_notify'] = $pNotify;
        }
        if (is_string($pRemark)) {
            $data['payment_remark'] = $pRemark;
        }
        if (is_numeric($pCoupon)) {
            $data['payment_coupon'] = $pCoupon;
        }
        // 更新支付行为
        $model->save($data);
        // 触发支付成功事件
        $this->app->event->trigger('PluginPaymentSuccess', $model->refresh());
        // 更新记录状态
        return $model->toArray();
    }

    /**
     * 获取并同步退款金额的支付单.
     * @param PluginPaymentRecord|string $record
     * @throws Exception
     */
    protected static function withPaymentByRefundTotal($record): PluginPaymentRecord
    {
        if (is_string($record)) {
            $record = PluginPaymentRecord::mk()->where(['code' => $record])->findOrEmpty();
        }
        if (!$record instanceof PluginPaymentRecord || $record->isEmpty()) {
            throw new Exception('无效的支付单！');
        }
        $total = Payment::totalRefundAmount($record->getAttr('code'));
        return $record->appendData([
            'refund_amount' => $total['amount'],
            'refund_payment' => $total['payment'],
            'refund_balance' => $total['balance'],
            'refund_integral' => $total['integral'],
            'refund_status' => intval($total['amount'] > 0),
        ], true);
    }

    /**
     * 获取账号编号.
     * @param ?int $unid 用户账号
     * @param ?int $usid 终端账号
     * @throws Exception
     */
    protected function withUserUnid(AccountInterface $account, ?int &$unid = 0, ?int &$usid = 0): int
    {
        sysvar('PluginPaymentUsid', $usid = intval($this->withUserField($account, 'id')));
        sysvar('PluginPaymentUnid', $unid = intval($this->withUserField($account, 'unid')));
        return $unid;
    }

    /**
     * 获取账号指定字段.
     * @return mixed|string
     * @throws Exception
     */
    protected function withUserField(AccountInterface $account, string $field)
    {
        $auth = $account->get();
        if (isset($auth[$field])) {
            return $auth[$field];
        }
        throw new Exception("获取 {$field} 字段值失败！");
    }

    /**
     * 获取通知地址
     * @param string $order 订单单号
     * @param string $scene 支付场景
     * @param array $extra 扩展数据
     */
    protected function withNotifyUrl(string $order, string $scene = 'order', array $extra = []): string
    {
        $data = ['scen' => $scene, 'order' => $order, 'channel' => $this->cfgCode];
        $vars = CodeExtend::enSafe64(json_encode($extra + $data, 64 | 256));
        return sysuri('@plugin-payment-notify', [], false, true) . "/{$vars}";
    }
}
