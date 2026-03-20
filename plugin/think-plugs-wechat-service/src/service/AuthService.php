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

namespace plugin\wechat\service\service;

use plugin\wechat\service\model\WechatAuth;
use think\admin\Service;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Exception;

/**
 * Class AuthService.
 *
 * @method static \WeChat\Card WeChatCard(string $appid) 微信卡券管理
 * @method static \WeChat\Custom WeChatCustom(string $appid) 微信客服消息
 * @method static \WeChat\Limit WeChatLimit(string $appid) 接口调用频次限制
 * @method static \WeChat\Media WeChatMedia(string $appid) 微信素材管理
 * @method static \WeChat\Menu WeChatMenu(string $appid) 微信菜单管理
 * @method static \WeChat\Oauth WeChatOauth(string $appid) 微信网页授权
 * @method static \WeChat\Pay WeChatPay(string $appid) 微信支付商户
 * @method static \WeChat\Product WeChatProduct(string $appid) 微信商店管理
 * @method static \WeChat\Qrcode WeChatQrcode(string $appid) 微信二维码管理
 * @method static \WeChat\Receive WeChatReceive(string $appid) 微信推送管理
 * @method static \WeChat\Scan WeChatScan(string $appid) 微信扫一扫接入管理
 * @method static \WeChat\Script WeChatScript(string $appid) 微信前端支持
 * @method static \WeChat\Shake WeChatShake(string $appid) 微信揺一揺周边
 * @method static \WeChat\Tags WeChatTags(string $appid) 微信用户标签管理
 * @method static \WeChat\Template WeChatTemplate(string $appid) 微信模板消息
 * @method static \WeChat\User WeChatUser(string $appid) 微信粉丝管理
 * @method static \WeChat\Wifi WeChatWifi(string $appid) 微信门店WIFI管理
 *
 * ----- WeMini -----
 * @method static \WeMini\Account WeMiniAccount(string $appid) 小程序账号管理
 * @method static \WeMini\Basic WeMiniBasic(string $appid) 小程序基础信息设置
 * @method static \WeMini\Code WeMiniCode(string $appid) 小程序代码管理
 * @method static \WeMini\Domain WeMiniDomain(string $appid) 小程序域名管理
 * @method static \WeMini\Tester WeMinitester(string $appid) 小程序成员管理
 * @method static \WeMini\User WeMiniUser(string $appid) 小程序帐号管理
 *                                                       --------------------
 * @method static \WeMini\Crypt WeMiniCrypt(array $options = []) 小程序数据加密处理
 * @method static \WeMini\Delivery WeMiniDelivery(array $options = []) 小程序即时配送
 * @method static \WeMini\Image WeMiniImage(array $options = []) 小程序图像处理
 * @method static \WeMini\Logistics WeMiniLogistics(array $options = []) 小程序物流助手
 * @method static \WeMini\Message WeMiniMessage(array $options = []) 小程序动态消息
 * @method static \WeMini\Ocr WeMiniOcr(array $options = []) 小程序ORC服务
 * @method static \WeMini\Plugs WeMiniPlugs(array $options = []) 小程序插件管理
 * @method static \WeMini\Poi WeMiniPoi(array $options = []) 小程序地址管理
 * @method static \WeMini\Qrcode WeMiniQrcode(array $options = []) 小程序二维码管理
 * @method static \WeMini\Security WeMiniSecurity(array $options = []) 小程序内容安全
 * @method static \WeMini\Soter WeMiniSoter(array $options = []) 小程序生物认证
 * @method static \WeMini\Template WeMiniTemplate(array $options = []) 小程序模板消息支持
 * @method static \WeMini\Total WeMiniTotal(array $options = []) 小程序数据接口
 *
 * ----- WePay -----
 * @method static \WePay\Bill WePayBill(string $appid) 微信商户账单及评论
 * @method static \WePay\Order WePayOrder(string $appid) 微信商户订单
 * @method static \WePay\Refund WePayRefund(string $appid) 微信商户退款
 * @method static \WePay\Coupon WePayCoupon(string $appid) 微信商户代金券
 * @method static \WePay\Redpack WePayRedpack(string $appid) 微信红包支持
 * @method static \WePay\Transfers WePayTransfers(string $appid) 微信商户打款到零钱
 * @method static \WePay\TransfersBank WePayTransfersBank(string $appid) 微信商户打款到银行卡
 *
 * ----- WeOpen -----
 * @method static \WeOpen\Login WeOpenLogin() 第三方微信登录
 * @method static \WeOpen\Service WeOpenService() 第三方服务
 *
 * ----- ThinkService -----
 * @method static ConfigService ThinkServiceConfig(string $appid) 平台服务配置
 */
class AuthService extends Service
{
    /**
     * 静态初始化对象
     * @return mixed
     * @throws \think\admin\Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function __callStatic(string $name, array $arguments)
    {
        $class = '-';
        foreach (['WeChat', 'WeMini', 'WeOpen', 'WePay', 'ThinkService'] as $type) {
            if (strpos($name, $type) === 0) {
                [, $class] = explode($type, $name);
                break;
            }
        }
        if ("{$type}{$class}" !== $name) {
            throw new \think\admin\Exception("class {$name} not defined.");
        }
        if (in_array($type, ['WeChat', 'WePay', 'WeMini', 'ThinkService'])) {
            if (empty($arguments[0])) {
                throw new \think\admin\Exception('Appid parameter must be passed in during initialization');
            }
        }
        $classname = "\\{$type}\\{$class}";
        if (in_array($type, ['WeChat', 'WeMini', 'WePay'])) {
            return new $classname(self::instance()->getWechatConfig($arguments[0]));
        }
        if ($type === 'ThinkService' && $class === 'Config') {
            return ConfigService::instance()->init($arguments[0]);
        }
        if ($type === 'WeOpen') {
            return new $classname(self::instance()->getServiceConfig());
        }
        throw new \think\admin\Exception("class {$classname} not defined.");
    }

    /**
     * 生成公众号授权信息.
     * @param array $info 生成授权信息
     */
    public static function buildAuthData(array $info): array
    {
        $info = array_change_key_case($info);
        $info['business_info'] = serialize($info['business_info']);
        $info['verify_type'] = $info['verify_type_info']['id'] != 0 ? '未认证' : '已认证';
        if (isset($info['func_info']) && is_array($info['func_info'])) {
            $funcinfo = array_column($info['func_info'], 'funcscope_category');
            $info['func_info'] = join(',', array_column($funcinfo, 'id'));
        }
        if (empty($info['miniprograminfo'])) {
            $info['service_type'] = $info['service_type_info']['id'] == 2 ? '服务号' : '订阅号';
            $info['miniprograminfo'] = '';
        } else {
            $info['service_type'] = '小程序';
            $info['miniprograminfo'] = serialize($info['miniprograminfo']);
        }
        $data = [
            'user_name' => $info['user_name'],
            'user_alias' => $info['alias'],
            'user_company' => $info['principal_name'],
            'user_signature' => $info['signature'],
            'user_nickname' => $info['nick_name'],
            'service_type' => $info['service_type'],
            'service_verify' => $info['verify_type'],
            'qrcode_url' => $info['qrcode_url'],
            'businessinfo' => $info['business_info'],
            'miniprograminfo' => $info['miniprograminfo'],
        ];
        if (isset($info['head_img'])) {
            $data['user_headimg'] = $info['head_img'];
        }
        $keys = 'func_info,expires_in,authorizer_appid,authorizer_access_token,authorizer_refresh_token';
        foreach (explode(',', $keys) as $key) {
            if (isset($info[$key])) {
                $data[$key] = $info[$key];
            }
        }
        return $data;
    }

    /**
     * 获取公众号配置参数.
     * @throws \think\admin\Exception
     */
    public function getWechatConfig(string $appid): array
    {
        $conifg = [
            'appid' => $appid,
            'token' => sysconf('service.component_token'),
            'appsecret' => sysconf('service.component_appsecret'),
            'encodingaeskey' => sysconf('service.component_encodingaeskey'),
            'cache_path' => $this->getCachePath(),
        ];
        $conifg['GetAccessTokenCallback'] = function ($authorizerAppid) {
            $map = ['authorizer_appid' => $authorizerAppid];
            $refreshToken = WechatAuth::mk()->where($map)->value('authorizer_refresh_token');
            if (empty($refreshToken)) {
                throw new \think\admin\Exception('The WeChat information is not configured.', '404');
            }
            // 刷新公众号原授权 AccessToken
            $result = AuthService::WeOpenService()->refreshAccessToken($authorizerAppid, $refreshToken);
            if (empty($result['authorizer_access_token']) || empty($result['authorizer_refresh_token'])) {
                throw new Exception($result['errmsg']);
            }
            // 更新公众号授权信息
            WechatAuth::mk()->where($map)->update([
                'authorizer_access_token' => $result['authorizer_access_token'],
                'authorizer_refresh_token' => $result['authorizer_refresh_token'],
            ]);
            return $result['authorizer_access_token'];
        };
        return $conifg;
    }

    /**
     * 获取服务平台配置参数.
     * @throws \think\admin\Exception
     */
    public function getServiceConfig(): array
    {
        return [
            'cache_path' => $this->getCachePath(),
            'component_appid' => sysconf('service.component_appid'),
            'component_token' => sysconf('service.component_token'),
            'component_appsecret' => sysconf('service.component_appsecret'),
            'component_encodingaeskey' => sysconf('service.component_encodingaeskey'),
        ];
    }

    /**
     * 获取缓存目录.
     */
    private function getCachePath(): string
    {
        return runpath('safefile/cache');
    }
}
