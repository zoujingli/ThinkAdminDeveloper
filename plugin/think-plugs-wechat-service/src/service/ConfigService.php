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
use think\admin\Exception;
use think\admin\Model;
use think\admin\Service;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use WeChat\Exceptions\InvalidResponseException;
use WeChat\Exceptions\LocalCacheException;

/**
 * 公众号授权配置
 * Class ConfigService.
 */
class ConfigService extends Service
{
    private const SERVICE_GROUP = 'wechat.service';

    /**
     * 数据查询条件.
     * @var array
     */
    private $map;

    /**
     * 当前微信APPID.
     * @var string
     */
    private $appid;

    /**
     * 当前微信配置.
     * @var Model
     */
    private $config;

    /**
     * 授权配置初始化.
     * @return $this
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function init(string $appid): ConfigService
    {
        $this->map = ['authorizer_appid' => $this->appid = $appid];
        $this->config = WechatAuth::mk()->where($this->map)->find();
        if (empty($this->config)) {
            throw new Exception(lang('公众号%s还没有授权！', [$appid]));
        }
        return $this;
    }

    /**
     * 获取当前公众号配置.
     */
    public function getConfig(): array
    {
        return $this->config->toArray();
    }

    /**
     * 设置微信接口通知URL地址
     * @param string $notify 接口通知URL地址
     * @throws Exception
     */
    public function setApiNotifyUri(string $notify): bool
    {
        if (empty($notify)) {
            throw new Exception(lang('请传入微信通知URL'));
        }
        return WechatAuth::mk()->where($this->map)->update(['appuri' => $notify]) !== false;
    }

    /**
     * 更新接口 AppKey.
     */
    public function updateApiAppkey(): string
    {
        $data = ['appkey' => md5(uniqid())];
        WechatAuth::mk()->where($this->map)->update($data);
        return $data['appkey'];
    }

    /**
     * 获取公众号的配置参数.
     * @param null|string $name 参数名称
     * @return array|string
     */
    public function config(?string $name = null)
    {
        return AuthService::WeChatScript($this->appid)->config->get($name);
    }

    /**
     * 微信网页授权.
     * @param string $oauthid 当前网页授权标识
     * @param string $source 当前网页授权URL地址(需包含域名的完整URL地址)
     * @param int $type 网页授权模式(0静默模式,1高级授权)
     * @return array<string, array|string>
     */
    public function oauth(string $oauthid, string $source, int $type = 0): array
    {
        $fans = $this->app->cache->get("{$this->appid}_{$oauthid}_fans", []);
        $token = $this->app->cache->get("{$this->appid}_{$oauthid}_token", []);
        $openid = $this->app->cache->get("{$this->appid}_{$oauthid}_openid", '');
        if (!empty($openid) && !empty($type) && !empty($fans)) {
            return ['openid' => $openid, 'token' => $token, 'fans' => $fans, 'url' => ''];
        }
        $mode = empty($type) ? 'snsapi_base' : 'snsapi_userinfo';
        $params = ['mode' => $type, 'oauthid' => $oauthid, 'enurl' => enbase64url($source)];
        $location = apiuri('wechat-service/push/oauth', [], false, true) . '?' . http_build_query($params);
        $oauthurl = AuthService::WeOpenService()->getOauthRedirect($this->appid, $location, $mode);
        return ['openid' => $openid, 'token' => $token, 'fans' => $fans, 'url' => $oauthurl];
    }

    /**
     * 微信网页JS签名.
     * @param string $url 当前会话URL地址(需包含域名的完整URL地址)
     * @throws InvalidResponseException
     * @throws LocalCacheException
     */
    public function jsSign(string $url): array
    {
        return AuthService::WeChatScript($this->appid)->getJsSign($url);
    }

    /**
     * @return array<string, string>
     */
    public static function getServiceSettings(): array
    {
        return [
            'component_appid' => self::getServiceSetting('component_appid'),
            'component_appsecret' => self::getServiceSetting('component_appsecret'),
            'component_token' => self::getServiceSetting('component_token'),
            'component_encodingaeskey' => self::getServiceSetting('component_encodingaeskey'),
            'ticket_push_date' => self::getServiceSetting('ticket_push_date'),
        ];
    }

    public static function getServiceSetting(string $name, string $default = ''): string
    {
        return strval(sysget(self::serviceKey($name), $default));
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function saveServiceSettings(array $data): void
    {
        foreach (['component_appid', 'component_appsecret', 'component_token', 'component_encodingaeskey'] as $name) {
            sysdata(self::serviceKey($name), trim(strval($data[$name] ?? '')));
        }
    }

    public static function markTicketPushDate(?string $time = null): void
    {
        sysdata(self::serviceKey('ticket_push_date'), $time ?? date('Y-m-d H:i:s'));
    }

    /**
     * @return array<string, string>
     */
    public static function buildAdminContext(): array
    {
        $settings = self::getServiceSettings();
        return [
            'ticketPushDate' => $settings['ticket_push_date'],
            'componentAppid' => $settings['component_appid'],
            'componentAppsecret' => $settings['component_appsecret'],
            'componentToken' => $settings['component_token'],
            'componentEncodingaeskey' => $settings['component_encodingaeskey'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function buildFormData(): array
    {
        $settings = self::getServiceSettings();
        return [
            'component_appid' => $settings['component_appid'],
            'component_appsecret' => $settings['component_appsecret'],
            'component_token' => $settings['component_token'],
            'component_encodingaeskey' => $settings['component_encodingaeskey'],
        ];
    }

    private static function serviceKey(string $name): string
    {
        return self::SERVICE_GROUP . '.' . ltrim($name, '.');
    }
}
