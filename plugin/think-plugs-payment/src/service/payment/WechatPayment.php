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

namespace plugin\payment\service\payment;

use plugin\payment\service\contract\PaymentInterface;
use plugin\payment\service\contract\PaymentUsageTrait;
use plugin\payment\service\Payment;
use plugin\payment\service\payment\wechat\WechatPaymentV2;
use plugin\payment\service\payment\wechat\WechatPaymentV3;
use think\admin\storage\LocalStorage;

/**
 * 微信商户支付调度
 * @class WechatPayment
 * @package plugin\payment\service\payment
 */
abstract class WechatPayment implements PaymentInterface
{
    use PaymentUsageTrait;

    protected const tradeTypes = [
        Payment::WECHAT_APP => 'APP',
        Payment::WECHAT_WAP => 'MWEB',
        Payment::WECHAT_GZH => 'JSAPI',
        Payment::WECHAT_XCX => 'JSAPI',
        Payment::WECHAT_QRC => 'NATIVE',
    ];

    /**
     * 初始化支付方式
     * @param string $code
     * @param string $type
     * @param array $params
     * @return PaymentInterface
     */
    public static function mk(string $code, string $type, array $params): PaymentInterface
    {
        if (isset($params['wechat_mch_ver']) && $params['wechat_mch_ver'] === 'v3') {
            /** @var PaymentInterface */
            return app(WechatPaymentV3::class, ['code' => $code, 'type' => $type, 'params' => $params]);
        } else {
            /** @var PaymentInterface */
            return app(WechatPaymentV2::class, ['code' => $code, 'type' => $type, 'params' => $params]);
        }
    }

    /**
     * 初始化支付方式
     * @return PaymentInterface
     */
    public function init(): PaymentInterface
    {
        $this->config['appid'] = $this->cfgParams['wechat_appid'];
        $this->config['mch_id'] = $this->cfgParams['wechat_mch_id'];
        $this->config['mch_key'] = $this->cfgParams['wechat_mch_key'] ?? '';
        $this->config['mch_v3_key'] = $this->cfgParams['wechat_mch_v3_key'] ?? '';
        $this->withCertConfig();
        $this->config['cache_path'] = syspath('runtime/wechat');
        return $this;
    }

    /**
     * 设置商户证书
     * @return void
     */
    private function withCertConfig()
    {
        if (empty($this->cfgParams['wechat_mch_cer_text'])) return;
        if (empty($this->cfgParams['wechat_mch_key_text'])) return;
        $local = LocalStorage::instance();
        $prefix = "wxpay/{$this->config['mch_id']}_";
        $sslKey = $prefix . md5($this->cfgParams['wechat_mch_key_text']) . '_key.pem';
        $sslCer = $prefix . md5($this->cfgParams['wechat_mch_cer_text']) . '_cert.pem';
        if (!$local->has($sslKey, true)) $local->set($sslKey, $this->cfgParams['wechat_mch_key_text'], true);
        if (!$local->has($sslCer, true)) $local->set($sslCer, $this->cfgParams['wechat_mch_cer_text'], true);
        $this->config['ssl_cer'] = $local->path($sslCer, true);
        $this->config['ssl_key'] = $local->path($sslKey, true);
        $this->config['cert_public'] = $this->config['ssl_cer'];
        $this->config['cert_private'] = $this->config['ssl_key'];
    }
}