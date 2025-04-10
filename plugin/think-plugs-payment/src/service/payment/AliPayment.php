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

use AliPay\App;
use AliPay\Wap;
use AliPay\Web;
use plugin\account\service\contract\AccountInterface;
use plugin\payment\service\contract\PaymentInterface;
use plugin\payment\service\contract\PaymentResponse;
use plugin\payment\service\contract\PaymentUsageTrait;
use plugin\payment\service\Payment;
use think\admin\Exception;
use think\Response;

/**
 * 支付宝支付配置
 * @class AliPayment
 * @package plugin\payment\service\payment
 */
class AliPayment implements PaymentInterface
{
    use PaymentUsageTrait;

    /**
     * 初始化支付配置
     * @return PaymentInterface
     */
    public function init(): PaymentInterface
    {
        $this->config = [
            // 沙箱模式
            'debug'       => false,
            // 应用ID
            'appid'       => $this->cfgParams['alipay_appid'],
            // 签名类型（RSA|RSA2）
            'sign_type'   => "RSA2",
            // 支付宝公钥 (1行填写，特别注意，这里是支付宝公钥，不是应用公钥，最好从开发者中心的网页上去复制)
            'public_key'  => $this->_trimCert($this->cfgParams['alipay_public_key']),
            // 支付宝私钥 (1行填写)
            'private_key' => $this->_trimCert($this->cfgParams['alipay_private_key']),
            // 支付成功通知地址
            'notify_url'  => '',
            // 网页支付回跳地址
            'return_url'  => '',
        ];
        return $this;
    }

    /**
     * 去除证书内容前后缀
     * @param string $content
     * @return string
     */
    private function _trimCert(string $content): string
    {
        return preg_replace(['/\s+/', '/-{5}.*?-{5}/'], '', $content);
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
            $this->config['notify_url'] = $this->withNotifyUrl($payCode);
            if (in_array($this->cfgType, [Payment::ALIPAY_WAP, Payment::ALIPAY_WEB])) {
                if (empty($payReturn)) {
                    throw new Exception('支付回跳地址不能为空！');
                } else {
                    $this->config['return_url'] = $payReturn;
                }
            }
            if ($this->cfgType === Payment::WECHAT_APP) {
                $payment = App::instance($this->config);
            } elseif ($this->cfgType === Payment::ALIPAY_WAP) {
                $payment = Wap::instance($this->config);
            } elseif ($this->cfgType === Payment::ALIPAY_WEB) {
                $payment = Web::instance($this->config);
            } else {
                throw new Exception("支付类型[{$this->cfgType}]暂时不支持！");
            }
            $param = ['out_trade_no' => $payCode, 'total_amount' => $payAmount, 'subject' => $orderTitle];
            if (!empty($orderRemark)) $param['body'] = $orderRemark;
            // 创建支付记录
            $data = $this->createAction($orderNo, $orderTitle, $orderAmount, $payCode, $payAmount);
            // 返回支付参数
            return $this->res->set(true, "创建支付成功！", $data, [$payment->apply($param)]);
        } catch (Exception $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * 支付通知处理
     * @param array $data
     * @param ?array $body
     * @return \think\Response
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function notify(array $data = [], ?array $body = null): Response
    {
        $notify = $body ?: App::instance($this->config)->notify();
        if (in_array($notify['trade_status'], ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            if ($this->updateAction($notify['out_trade_no'], $notify['trade_no'], $notify['total_amount'])) {
                return response('success');
            } else {
                return response('error');
            }
        } else {
            return response('success');
        }
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
            // 记录退款数据
            if (floatval($amount) <= 0) return [1, '无需退款！'];
            static::syncRefund($pcode, $rcode, $amount, $reason);
            // 发起退款申请
            App::instance($this->config)->refund([
                'out_trade_no'   => $pcode,
                'out_request_no' => $rcode,
                'refund_amount'  => $amount,
            ]);
            return [1, '发起退款成功！'];
        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * 查询订单数据
     * @param string $pcode
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function query(string $pcode): array
    {
        return App::instance($this->config)->query($pcode);
    }
}