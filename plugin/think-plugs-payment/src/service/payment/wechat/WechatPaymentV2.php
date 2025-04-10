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

declare (strict_types=1);

namespace plugin\payment\service\payment\wechat;

use plugin\account\service\contract\AccountInterface;
use plugin\payment\model\PluginPaymentRefund;
use plugin\payment\service\contract\PaymentInterface;
use plugin\payment\service\contract\PaymentResponse;
use plugin\payment\service\Payment;
use plugin\payment\service\payment\WechatPayment;
use think\admin\Exception;
use think\Response;
use WePay\Order;
use WePay\Refund;

/**
 * 微信支付 V2 版本
 * @class WechatPaymentV2
 * @package plugin\payment\service\payment\wechat
 */
class WechatPaymentV2 extends WechatPayment
{
    /** @var Order */
    private $payment;

    /**
     * 初始化支付配置
     * @return PaymentInterface
     */
    public function init(): PaymentInterface
    {
        parent::init();
        $this->payment = Order::instance($this->config);
        return $this;
    }

    /**
     * 创建支付订单
     * @param AccountInterface $account 支付账号
     * @param string $orderNo 交易订单单号
     * @param string $orderTitle 交易订单标题
     * @param string $orderAmount 订单支付金额（元）
     * @param string $payAmount 本次交易金额
     * @param string $payRemark 交易订单描述
     * @param string $payReturn 支付回跳地址
     * @param string $payImages 支付凭证图片
     * @param string $payCoupon 优惠券编号
     * @return PaymentResponse
     * @throws \think\admin\Exception
     */
    public function create(AccountInterface $account, string $orderNo, string $orderTitle, string $orderAmount, string $payAmount, string $payRemark = '', string $payReturn = '', string $payImages = '', string $payCoupon = ''): PaymentResponse
    {
        try {
            $this->checkLeaveAmount($orderNo, $payAmount, $orderAmount);
            [$payCode] = [Payment::withPaymentCode(), $this->withUserUnid($account)];
            $body = empty($orderRemark) ? $orderTitle : ($orderTitle . '-' . $orderRemark);
            $data = [
                'body'             => $body,
                'openid'           => $this->withUserField($account, 'openid'),
                'attach'           => $this->cfgCode,
                'out_trade_no'     => $payCode,
                'trade_type'       => static::tradeTypes[$this->cfgType] ?? '',
                'total_fee'        => intval(floatval($payAmount) * 100),
                'notify_url'       => $this->withNotifyUrl($payCode),
                'spbill_create_ip' => $this->app->request->ip(),
            ];
            if (empty($data['openid'])) unset($data['openid']);
            $info = $this->payment->create($data);
            if ($info['return_code'] === 'SUCCESS' && $info['result_code'] === 'SUCCESS') {
                // 支付参数过滤
                if ($this->cfgType === Payment::WECHAT_APP) {
                    $param = $this->payment->appParams($info['prepay_id']);
                } elseif (isset($info['prepay_id'])) {
                    $param = $this->payment->jsapiParams($info['prepay_id']);
                } else {
                    $param = $info;
                }
                // 创建支付记录
                $data = $this->createAction($orderNo, $orderTitle, $orderAmount, $payCode, $payAmount);
                // 返回支付参数
                return $this->res->set(true, "创建支付成功！", $data, $param);
            }
            throw new Exception($info['err_code_des'] ?? '获取预支付码失败！');
        } catch (Exception $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * 查询微信支付订单
     * @param string $pcode 支付号
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function query(string $pcode): array
    {
        $result = $this->payment->query(['out_trade_no' => $pcode]);
        if (isset($result['return_code']) && isset($result['result_code']) && isset($result['attach'])) {
            if ($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS') {
                $this->updateAction($result['out_trade_no'], strval($result['cash_fee'] / 100), $result['transaction_id']);
            }
        }
        return $result;
    }

    /**
     * 支付通知处理
     * @param array $data
     * @param ?array $body
     * @return \think\Response
     * @throws \WeChat\Exceptions\InvalidDecryptException
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \think\admin\Exception
     */
    public function notify(array $data = [], ?array $body = null): Response
    {
        if ($data['scen'] === 'order') {
            // 支付通知处理
            $notify = $this->payment->getNotify($body);
            p($notify, false, 'notify_payment_v2');
            if ($notify['result_code'] == 'SUCCESS' && $notify['return_code'] == 'SUCCESS') {
                [$pCode, $pTrade] = [$notify['out_trade_no'], $notify['transaction_id']];
                [$pAmount, $pCoupon] = [strval($notify['cash_fee'] / 100), strval(($notify['coupon_fee'] ?? 0) / 100)];
                if (!$this->updateAction($pCode, $pTrade, $pAmount, null, $pCoupon, $notify)) {
                    return xml(['return_code' => 'ERROR', 'return_msg' => '数据更新失败']);
                }
            }
        } elseif ($data['scen'] === 'refund') {
            // 退款通知信息
            $notify = Refund::instance($this->config)->getNotify($body);
            p($notify, false, 'notify_refund_v2');
            if (!empty($notify['result']) && is_array($notify['result'])) {
                $notify = array_merge($notify, $notify['result']);
                unset($notify['result'], $notify['req_info']);
            }
            if (isset($notify['refund_status']) && $notify['refund_status'] == 'SUCCESS') {
                $refund = PluginPaymentRefund::mk()->where(['code' => $notify['out_refund_no']])->findOrEmpty();
                if ($refund->isEmpty()) return xml(['return_code' => 'ERROR', 'return_msg' => '数据更新失败']);
                $refund->save([
                    'refund_time'    => date('Y-m-d H:i:s', strtotime($notify['success_time'])),
                    'refund_trade'   => $notify['transaction_id'],
                    'refund_scode'   => $notify['refund_status'],
                    'refund_status'  => 1,
                    'refund_notify'  => json_encode($notify, 64 | 256),
                    'refund_account' => $notify['refund_recv_accout'] ?? '',
                ]);
                static::syncRefund($refund->getAttr('record_code'));
            }
        }
        return xml(['return_code' => 'SUCCESS', 'return_msg' => 'OK']);
    }

    /**
     * 发起支付退款
     * @param string $pcode
     * @param string $amount
     * @param string $reason
     * @param ?string $rcode
     * @return array [状态, 消息]
     * @throws \think\admin\Exception
     */
    public function refund(string $pcode, string $amount, string $reason = '', ?string &$rcode = null): array
    {
        try {
            // 记录退款
            if (floatval($amount) <= 0) return [1, '无需退款！'];
            $record = static::syncRefund($pcode, $rcode, $amount, $reason);
            // 发起退款申请
            $options = [
                'out_trade_no'  => $pcode,
                'out_refund_no' => $rcode,
                'total_fee'     => intval($record->getAttr('payment_amount') * 100),
                'refund_fee'    => intval(floatval($amount) * 100),
                'notify_url'    => static::withNotifyUrl($rcode, 'refund'),
            ];
            if (strlen($reason) > 0) $options['refund_desc'] = $reason;
            $result = Refund::instance($this->config)->create($options);
            if (in_array($result['return_code'] ?? $result['result_code'], ['SUCCESS', 'PROCESSING'])) {
                return [1, '已提交退款！'];
            } else {
                throw new Exception($result['err_code_des'] ?? $result['result_code'], 0);
            }
        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }
}