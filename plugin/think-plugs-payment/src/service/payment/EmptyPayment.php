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

namespace plugin\payment\service\payment;

use plugin\account\service\contract\AccountInterface;
use plugin\payment\service\contract\PaymentInterface;
use plugin\payment\service\contract\PaymentResponse;
use plugin\payment\service\contract\PaymentUsageTrait;
use plugin\payment\service\Payment;
use think\admin\Exception;
use think\admin\extend\CodeExtend;
use think\Response;

/**
 * 空支付支付方式
 * @class EmptyPayment
 * @package plugin\payment\service\payment
 */
class EmptyPayment implements PaymentInterface
{
    use PaymentUsageTrait;

    /**
     * 初始化支付配置
     * @return PaymentInterface
     */
    public function init(): PaymentInterface
    {
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
            [$payCode] = [Payment::withPaymentCode(), $this->withUserUnid($account)];
            $this->createAction($orderNo, $orderTitle, $orderAmount, $payCode, $payAmount);
            $data = $this->updateAction($payCode, CodeExtend::uniqidNumber(18, 'EMT'), $payAmount, '无需支付');
            return $this->res->set(true, "订单无需支付！", $data);
        } catch (Exception $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * 订单主动查询
     * @param string $pcode
     * @return array
     */
    public function query(string $pcode): array
    {
        return [];
    }

    /**
     * 支付通知处理
     * @param array $data
     * @param ?array $body
     * @return \think\Response
     */
    public function notify(array $data = [], ?array $body = null): Response
    {
        return response('SUCCESS');
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
            if (floatval($amount) <= 0) return [1, '无需退款！'];
            static::syncRefund($pcode, $rcode, $amount, $reason);
            return [1, '发起退款成功！'];
        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }
}