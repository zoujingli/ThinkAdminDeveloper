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
use plugin\payment\model\PluginPaymentRecord;
use plugin\payment\service\contract\PaymentInterface;
use plugin\payment\service\contract\PaymentResponse;
use plugin\payment\service\contract\PaymentUsageTrait;
use plugin\payment\service\Payment;
use think\admin\Exception;
use think\Response;

/**
 * 用户优惠券抵扣
 * @class CouponPayment
 * @package plugin\payment\service\payment
 */
class CouponPayment implements PaymentInterface
{
    use PaymentUsageTrait;

    /**
     * 初始化支付配置
     * @return $this
     */
    public function init(): PaymentInterface
    {
        return $this;
    }

    /**
     * 订单信息查询
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
            // 记录并退回
            static::syncRefund($pcode, $rcode, $amount, $reason);
            return [1, '发起退款成功！'];
        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
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
            // 检查优惠券是否已使用
            if (empty($payCoupon)) throw new Exception("无效优惠券！");
            $where = ['payment_trade' => $payCoupon, 'refund_status' => 0];
            $record = PluginPaymentRecord::mk()->where($where)->findOrEmpty();
            if ($record->isExists() && $record->getAttr('order_no') !== $payCoupon) {
                throw new Exception("优惠券已使用！");
            }
            // 检查剩余金额
            $this->checkLeaveAmount($orderNo, $payAmount, $orderAmount);
            // 创建支付行为
            [$payCode] = [Payment::withPaymentCode(), $this->withUserUnid($account)];
            $this->createAction($orderNo, $orderTitle, $orderAmount, $payCode, $payAmount, '', $payAmount);
            // 更新支付行为
            $data = $this->updateAction($payCode, $payCoupon, $payAmount, '使用优惠券抵扣', $payAmount);
            // 返回支付结果
            return $this->res->set(true, '优惠券抵扣完成！', $data);
        } catch (Exception $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }
}