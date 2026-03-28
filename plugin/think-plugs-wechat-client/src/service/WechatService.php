<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdminDeveloper
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | Official Website: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | Licensed: https://mit-license.org
 * | Disclaimer: https://thinkadmin.top/disclaimer
 * | Vip Rights: https://thinkadmin.top/vip-introduce
 * +----------------------------------------------------------------------
 * | Gitee Repository: https://gitee.com/zoujingli/ThinkAdmin
 * | Github Repository: https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */
// | Wechat Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 Anyon <zoujingli@qq.com>
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// +----------------------------------------------------------------------
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wechat-client
// +----------------------------------------------------------------------

namespace plugin\wechat\client\service;

use plugin\system\storage\LocalStorage;
use think\admin\Exception;
use think\admin\Library;
use think\admin\Service;
use think\admin\service\JsonRpcHttpClient;
use think\exception\HttpResponseException;
use think\Response;
use WeChat\Exceptions\InvalidResponseException;
use WeChat\Exceptions\LocalCacheException;

/**
 * 微信接口调度服务
 * @class WechatService
 *
 * @method static \WeChat\Card WeChatCard() 微信卡券管理
 * @method static \WeChat\Custom WeChatCustom() 微信客服消息
 * @method static \WeChat\Limit WeChatLimit() 接口调用频次限制
 * @method static \WeChat\Media WeChatMedia() 微信素材管理
 * @method static \WeChat\Draft WeChatDraft() 微信草稿箱管理
 * @method static \WeChat\Menu WeChatMenu() 微信菜单管理
 * @method static \WeChat\Oauth WeChatOauth() 微信网页授权
 * @method static \WeChat\Pay WeChatPay() 微信支付商户
 * @method static \WeChat\Product WeChatProduct() 微信商店管理
 * @method static \WeChat\Qrcode WeChatQrcode() 微信二维码管理
 * @method static \WeChat\Receive WeChatReceive() 微信推送管理
 * @method static \WeChat\Scan WeChatScan() 微信扫一扫接入管理
 * @method static \WeChat\Script WeChatScript() 微信前端支持
 * @method static \WeChat\Shake WeChatShake() 微信揺一揺周边
 * @method static \WeChat\Tags WeChatTags() 微信用户标签管理
 * @method static \WeChat\Template WeChatTemplate() 微信模板消息
 * @method static \WeChat\User WeChatUser() 微信粉丝管理
 * @method static \WeChat\Wifi WeChatWifi() 微信门店WIFI管理
 * @method static \WeChat\Freepublish WeChatFreepublish() 发布能力
 *
 * ----- WeMini -----
 * @method static \WeMini\Account WeMiniAccount() 小程序账号管理
 * @method static \WeMini\Basic WeMiniBasic() 小程序基础信息设置
 * @method static \WeMini\Code WeMiniCode() 小程序代码管理
 * @method static \WeMini\Domain WeMiniDomain() 小程序域名管理
 * @method static \WeMini\Tester WeMinitester() 小程序成员管理
 * @method static \WeMini\User WeMiniUser() 小程序帐号管理
 *                                          --------------------
 * @method static \WeMini\Crypt WeMiniCrypt() 小程序数据加密处理
 * @method static \WeMini\Delivery WeMiniDelivery() 小程序即时配送
 * @method static \WeMini\Guide WeMiniGuide() 小程序导购助手
 * @method static \WeMini\Image WeMiniImage() 小程序图像处理
 * @method static \WeMini\Live WeMiniLive() 小程序直播接口
 * @method static \WeMini\Logistics WeMiniLogistics() 小程序物流助手
 * @method static \WeMini\Newtmpl WeMiniNewtmpl() 公众号小程序订阅消息支持
 * @method static \WeMini\Message WeMiniMessage() 小程序动态消息
 * @method static \WeMini\Operation WeMiniOperation() 小程序运维中心
 * @method static \WeMini\Ocr WeMiniOcr() 小程序ORC服务
 * @method static \WeMini\Plugs WeMiniPlugs() 小程序插件管理
 * @method static \WeMini\Poi WeMiniPoi() 小程序地址管理
 * @method static \WeMini\Qrcode WeMiniQrcode() 小程序二维码管理
 * @method static \WeMini\Security WeMiniSecurity() 小程序内容安全
 * @method static \WeMini\Soter WeMiniSoter() 小程序生物认证
 * @method static \WeMini\Template WeMiniTemplate() 小程序模板消息支持
 * @method static \WeMini\Total WeMiniTotal() 小程序数据接口
 * @method static \WeMini\Scheme WeMiniScheme() 小程序URL-Scheme
 * @method static \WeMini\Search WeMiniSearch() 小程序搜索
 * @method static \WeMini\Shipping WeMiniShipping() 小程序发货信息管理服务
 *
 * ----- WePay -----
 * @method static \WePay\Bill WePayBill() 微信商户账单及评论
 * @method static \WePay\Order WePayOrder() 微信商户订单
 * @method static \WePay\Refund WePayRefund() 微信商户退款
 * @method static \WePay\Coupon WePayCoupon() 微信商户代金券
 * @method static \WePay\Custom WePayCustom() 微信扩展上报海关
 * @method static \WePay\ProfitSharing WePayProfitSharing() 微信分账
 * @method static \WePay\Redpack WePayRedpack() 微信红包支持
 * @method static \WePay\Transfers WePayTransfers() 微信商户打款到零钱
 * @method static \WePay\TransfersBank WePayTransfersBank() 微信商户打款到银行卡
 *
 * ----- WePayV3 -----
 * @method static \WePayV3\Order WePayV3Order() 直连商户|订单支付接口
 * @method static \WePayV3\Transfers WePayV3Transfers() 微信商家转账到零钱
 * @method static \WePayV3\ProfitSharing WePayV3ProfitSharing() 微信商户分账
 *
 * ----- WeOpen -----
 * @method static \WeOpen\Login WeOpenLogin() 第三方微信登录
 * @method static \WeOpen\Service WeOpenService() 第三方服务
 *
 * ----- ThinkService -----
 * @method static mixed ThinkServiceConfig() 平台服务配置
 */
class WechatService extends Service
{
    private const CLIENT_GROUP = 'wechat.client';

    private const DEFAULT_SERVICE_AUTH_URL = 'https://open.cuci.cc/service/api.push/auth?source=SOURCE';

    private const DEFAULT_SERVICE_JSONRPC = 'https://open.cuci.cc/service/api.client/jsonrpc?token=TOKEN';

    private const MENU_DATA_KEY = 'wechat_menu_data';

    /**
     * 静态初始化对象
     * @return mixed
     * @throws Exception
     */
    public static function __callStatic(string $name, array $arguments)
    {
        [$type, $base, $class] = self::parseName($name);
        if ("{$type}{$base}" !== $name) {
            throw new Exception("抱歉，实例 {$name} 不符合规则！");
        }
        if (static::config('type') === 'api' || in_array($type, ['WePay', 'WePayV3'], true)) {
            if (class_exists($class)) {
                return new $class($type === 'WeMini' ? static::getWxconf() : static::getConfig());
            }
            throw new Exception("抱歉，接口模式无法实例 {$class} 对象！");
        } else {
            [$appid, $appkey] = [static::config('thr_appid'), static::config('thr_appkey')];
            $data = ['class' => $name, 'appid' => $appid, 'time' => time(), 'nostr' => uniqid()];
            $data['sign'] = md5("{$data['class']}#{$appid}#{$appkey}#{$data['time']}#{$data['nostr']}");
            // 创建远程连接，默认使用 JSON-RPC 方式调用接口
            $token = enbase64url(json_encode($data, JSON_UNESCAPED_UNICODE));
            $jsonrpc = static::config('service_jsonrpc') ?: 'https://open.cuci.cc/service/api.client/jsonrpc?token=TOKEN';
            return new JsonRpcHttpClient(str_replace('token=TOKEN', "token={$token}", $jsonrpc));
        }
    }

    /**
     * 获取当前微信APPID.
     * @throws Exception
     */
    public static function getAppid(): string
    {
        if (static::getType() === 'api') {
            return static::config('appid');
        }
        return static::config('thr_appid');
    }

    /**
     * 获取接口授权模式.
     * @throws Exception
     */
    public static function getType(): string
    {
        $type = strtolower(static::config('type'));
        if (in_array($type, ['api', 'thr'], true)) {
            return $type;
        }
        throw new Exception('请在后台配置微信对接授权模式');
    }

    /**
     * 获取公众号配置参数.
     * @param bool $ispay 获取支付参数
     * @throws Exception
     */
    public static function getConfig(bool $ispay = false): array
    {
        $config = [
            'appid' => static::getAppid(),
            'token' => static::config('token'),
            'appsecret' => static::config('appsecret'),
            'encodingaeskey' => static::config('encodingaeskey'),
            'cache_path' => runpath('runtime/wechat'),
        ];
        return $ispay ? static::withWxpayCert($config) : $config;
    }

    /**
     * 获取小程序配置参数.
     * @param bool $ispay 获取支付参数
     * @throws Exception
     */
    public static function getWxconf(bool $ispay = false): array
    {
        $wxapp = static::getWxappConfig();
        $config = [
            'appid' => $wxapp['appid'] ?? '',
            'appsecret' => $wxapp['appkey'] ?? '',
            'cache_path' => runpath('runtime/wechat'),
        ];
        return $ispay ? static::withWxpayCert($config) : $config;
    }

    /**
     * 处理支付证书配置.
     * @throws Exception
     */
    public static function withWxpayCert(array $options): array
    {
        // 文本模式主要是为了解决分布式部署
        $data = static::getPaymentConfig();
        if (empty($data['mch_id'])) {
            throw new Exception('无效的支付配置！');
        }
        $name1 = sprintf('wxpay/%s_%s_cer.pem', $data['mch_id'], md5($data['ssl_cer_text']));
        $name2 = sprintf('wxpay/%s_%s_key.pem', $data['mch_id'], md5($data['ssl_key_text']));
        $local = LocalStorage::instance();
        if ($local->has($name1, true) && $local->has($name2, true)) {
            $sslCer = $local->path($name1, true);
            $sslKey = $local->path($name2, true);
        } else {
            $sslCer = $local->set($name1, $data['ssl_cer_text'], true)['file'];
            $sslKey = $local->set($name2, $data['ssl_key_text'], true)['file'];
        }
        $options['mch_id'] = $data['mch_id'];
        $options['mch_key'] = $data['mch_key'];
        $options['mch_v3_key'] = $data['mch_v3_key'];
        $options['ssl_cer'] = $sslCer;
        $options['ssl_key'] = $sslKey;
        $options['cert_public'] = $sslCer;
        $options['cert_private'] = $sslKey;
        $options['mp_cert_serial'] = $data['mch_pay_sid'] ?? '';
        $options['mp_cert_content'] = $data['ssl_pay_text'] ?? '';
        return $options;
    }

    /**
     * 获取网页授权标识.
     */
    public static function getOauthId(?string $source = null): string
    {
        $oauthid = trim(strval(Library::$sapp->request->get('oauthid', '')));
        if ($oauthid === '' && !empty($source)) {
            parse_str(strval(parse_url($source, PHP_URL_QUERY) ?: ''), $params);
            $oauthid = trim(strval($params['oauthid'] ?? ''));
        }
        if ($oauthid === '') {
            parse_str(strval(parse_url(strval(Library::$sapp->request->server('http_referer', '')), PHP_URL_QUERY) ?: ''), $params);
            $oauthid = trim(strval($params['oauthid'] ?? ''));
        }
        return $oauthid !== '' ? $oauthid : md5(uniqid('wechat_oauth_', true));
    }

    /**
     * 通过网页授权获取粉丝信息.
     * @param string $source 回跳URL地址
     * @param int $isfull 获取资料模式
     * @param bool $redirect 是否直接跳转
     * @throws InvalidResponseException
     * @throws LocalCacheException
     * @throws Exception
     */
    public static function getWebOauthInfo(string $source, int $isfull = 0, bool $redirect = true): array
    {
        [$oauthid, $appid] = [static::getOauthId($source), static::getAppid()];
        $openid = Library::$sapp->cache->get("{$oauthid}_openid");
        $userinfo = Library::$sapp->cache->get("{$oauthid}_fansinfo");
        if ((empty($isfull) && !empty($openid)) || (!empty($isfull) && !empty($openid) && !empty($userinfo))) {
            empty($userinfo) || FansService::set($userinfo, $appid);
            return ['openid' => $openid, 'fansinfo' => $userinfo];
        }
        if (static::getType() === 'api') {
            // 解析 GET 参数
            $query = parse_url($source, PHP_URL_QUERY);
            parse_str(is_string($query) ? $query : '', $params);
            $getVars = [
                'code' => $params['code'] ?? input('code', ''),
                'rcode' => $params['rcode'] ?? input('rcode', ''),
                'state' => $params['state'] ?? input('state', ''),
            ];
            $wechat = static::WeChatOauth();
            if ($getVars['state'] !== $appid || empty($getVars['code'])) {
                $params['rcode'] = enbase64url($source);
                $params['oauthid'] = $oauthid;
                $location = strstr("{$source}?", '?', true) . '?' . http_build_query($params);
                $oauthurl = $wechat->getOauthRedirect($location, $appid, $isfull ? 'snsapi_userinfo' : 'snsapi_base');
                throw new HttpResponseException(self::createRedirect($oauthurl, $redirect));
            }
            if (($token = $wechat->getOauthAccessToken($getVars['code'])) && isset($token['openid'])) {
                $openid = $token['openid'];
                // 如果是虚拟账号，不保存会话信息，下次重新授权
                if (empty($token['is_snapshotuser'])) {
                    Library::$sapp->cache->set("{$oauthid}_openid", $openid, 3600);
                }
                if ($isfull && isset($token['access_token'])) {
                    $userinfo = $wechat->getUserInfo($token['access_token'], $openid);
                    // 如果是虚拟账号，不保存会话信息，下次重新授权
                    if (empty($token['is_snapshotuser'])) {
                        $userinfo['is_snapshotuser'] = 0;
                        // 缓存用户信息
                        Library::$sapp->cache->set("{$oauthid}_fansinfo", $userinfo, 3600);
                        FansService::set($userinfo, $appid);
                    } else {
                        $userinfo['is_snapshotuser'] = 1;
                    }
                }
            }
            if ($getVars['rcode']) {
                throw new HttpResponseException(self::createRedirect(debase64url($getVars['rcode']), $redirect));
            }
            if ((empty($isfull) && !empty($openid)) || (!empty($isfull) && !empty($openid) && !empty($userinfo))) {
                return ['openid' => $openid, 'fansinfo' => $userinfo];
            }
            throw new Exception('Query params [rcode] not find.');
        } else {
            $result = static::ThinkServiceConfig()->oauth(self::getOauthId($source), $source, $isfull);
            [$openid, $userinfo] = [$result['openid'] ?? '', $result['fans'] ?? []];
            // 如果是虚拟账号，不保存会话信息，下次重新授权
            if (empty($result['token']['is_snapshotuser'])) {
                Library::$sapp->cache->set("{$oauthid}_openid", $openid, 3600);
                Library::$sapp->cache->set("{$oauthid}_fansinfo", $userinfo, 3600);
            }
            if ((empty($isfull) && !empty($openid)) || (!empty($isfull) && !empty($openid) && !empty($userinfo))) {
                empty($result['token']['is_snapshotuser']) && empty($userinfo) || FansService::set($userinfo, $appid);
                return ['openid' => $openid, 'fansinfo' => $userinfo];
            }
            throw new HttpResponseException(self::createRedirect($result['url'], $redirect));
        }
    }

    /**
     * 获取微信网页JSSDK签名参数.
     * @param null|string $location 签名地址
     * @throws InvalidResponseException
     * @throws LocalCacheException
     * @throws Exception
     */
    public static function getWebJssdkSign(?string $location = null): array
    {
        $location = $location ?: Library::$sapp->request->url(true);
        if (static::getType() === 'api') {
            return static::WeChatScript()->getJsSign($location);
        }
        return static::ThinkServiceConfig()->jsSign($location);
    }

    /**
     * 读取配置参数.
     */
    public static function config(string $name, string $default = ''): string
    {
        $config = static::getClientConfig();
        return strval($config[$name] ?? $default);
    }

    /**
     * 获取微信客户端配置。
     *
     * @return array<string, string>
     */
    public static function getClientConfig(): array
    {
        return [
            'type' => static::normalizeClientType(static::clientValue('type', 'api')),
            'token' => static::clientValue('token'),
            'appid' => static::clientValue('appid'),
            'appsecret' => static::clientValue('appsecret'),
            'encodingaeskey' => static::clientValue('encodingaeskey'),
            'thr_appid' => static::clientValue('thr_appid'),
            'thr_appkey' => static::clientValue('thr_appkey'),
            'service_authurl' => static::clientValue('service_authurl', self::DEFAULT_SERVICE_AUTH_URL),
            'service_jsonrpc' => static::clientValue('service_jsonrpc', self::DEFAULT_SERVICE_JSONRPC),
        ];
    }

    /**
     * @return array{auth_url:string,json_rpc:string}
     */
    public static function getJsonRpcConfig(): array
    {
        $config = static::getClientConfig();
        return [
            'auth_url' => $config['service_authurl'],
            'json_rpc' => $config['service_jsonrpc'],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function savePostedClientConfig(array $data): void
    {
        foreach ($data as $name => $value) {
            $name = strval($name);
            if (!str_starts_with($name, 'wechat.')) {
                continue;
            }

            static::saveClientValue(substr($name, 7), $value);
        }
    }

    public static function bindThirdPartyClient(string $appid, string $appkey): void
    {
        static::saveClientValue('type', 'thr');
        static::saveClientValue('thr_appid', trim($appid));
        static::saveClientValue('thr_appkey', trim($appkey));
    }

    /**
     * @param array{auth_url:string,json_rpc:string} $data
     */
    public static function saveJsonRpcConfig(array $data): void
    {
        static::saveClientValue('service_authurl', trim(strval($data['auth_url'] ?? self::DEFAULT_SERVICE_AUTH_URL)));
        static::saveClientValue('service_jsonrpc', trim(strval($data['json_rpc'] ?? self::DEFAULT_SERVICE_JSONRPC)));
    }

    /**
     * @return array<string, string>
     */
    public static function getWxappConfig(): array
    {
        $data = sysdata('plugin.wechat.wxapp');
        return is_array($data) ? $data : [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function saveWxappConfig(array $data): void
    {
        sysdata('plugin.wechat.wxapp', $data);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPaymentConfig(): array
    {
        $data = sysdata('plugin.wechat.payment');
        return is_array($data) ? $data : [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function savePaymentConfig(array $data): void
    {
        sysdata('plugin.wechat.payment', $data);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getMenuData(): array
    {
        $data = sysdata(self::MENU_DATA_KEY);
        return is_array($data) ? $data : [];
    }

    /**
     * @param array<int, array<string, mixed>> $data
     */
    public static function saveMenuData(array $data): void
    {
        sysdata(self::MENU_DATA_KEY, $data);
    }

    /**
     * 解析调用对象名称.
     */
    private static function parseName(string $name): array
    {
        foreach (['WeChat', 'WeMini', 'WeOpen', 'WePayV3', 'WePay', 'ThinkService'] as $type) {
            if (strpos($name, $type) === 0) {
                [, $base] = explode($type, $name);
                return [$type, $base, "\\{$type}\\{$base}"];
            }
        }
        return ['-', '-', $name];
    }

    /**
     * 网页授权链接跳转.
     * @param string $location 跳转链接
     * @param bool $redirect 强制跳转
     */
    private static function createRedirect(string $location, bool $redirect = true): Response
    {
        return $redirect ? redirect($location) : response(join(";\n", [
            sprintf("location.replace('%s')", $location), '',
        ]));
    }

    private static function clientValue(string $name, string $default = ''): string
    {
        return trim(strval(sysget(self::CLIENT_GROUP . '.' . ltrim($name, '.'), $default)));
    }

    private static function saveClientValue(string $name, mixed $value): void
    {
        if ($name === 'type') {
            $value = static::normalizeClientType(strval($value));
        }
        sysdata(self::CLIENT_GROUP . '.' . ltrim($name, '.'), $value);
    }

    private static function normalizeClientType(string $type): string
    {
        return in_array(strtolower($type), ['api', 'thr'], true) ? strtolower($type) : 'api';
    }
}
