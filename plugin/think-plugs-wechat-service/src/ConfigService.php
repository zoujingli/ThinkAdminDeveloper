<?php

// +----------------------------------------------------------------------
// | Wechat Service Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 Anyon <zoujingli@qq.com>
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wechat-service
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wechat-service
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wechat\service;

use plugin\wechat\service\model\WechatAuth;
use think\admin\Exception;
use think\admin\Model;
use think\admin\Service;

/**
 * 公众号授权配置
 * Class ConfigService
 * @package plugin\wechat\service
 */
class ConfigService extends Service
{
    /**
     * 数据查询条件
     * @var array
     */
    private $map;

    /**
     * 当前微信APPID
     * @var string
     */
    private $appid;

    /**
     * 当前微信配置
     * @var Model
     */
    private $config;

    /**
     * 授权配置初始化
     * @param string $appid
     * @return $this
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function init(string $appid): ConfigService
    {
        $this->map = ['authorizer_appid' => $this->appid = $appid];
        $this->config = WechatAuth::mk()->where($this->map)->find();
        if (empty($this->config)) throw new Exception("公众号{$appid}还没有授权！");
        return $this;
    }

    /**
     * 获取当前公众号配置
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config->toArray();
    }

    /**
     * 设置微信接口通知URL地址
     * @param string $notify 接口通知URL地址
     * @return boolean
     * @throws Exception
     */
    public function setApiNotifyUri(string $notify): bool
    {
        if (empty($notify)) throw new Exception('请传入微信通知URL');
        return WechatAuth::mk()->where($this->map)->update(['appuri' => $notify]) !== false;
    }

    /**
     * 更新接口 AppKey
     * @return string
     */
    public function updateApiAppkey(): string
    {
        $data = ['appkey' => md5(uniqid())];
        WechatAuth::mk()->where($this->map)->update($data);
        return $data['appkey'];
    }

    /**
     * 获取公众号的配置参数
     * @param null|string $name 参数名称
     * @return array|string
     */
    public function config(?string $name = null)
    {
        return AuthService::WeChatScript($this->appid)->config->get($name);
    }

    /**
     * 微信网页授权
     * @param string $sessid 当前会话id(可用session_id()获取)
     * @param string $source 当前会话URL地址(需包含域名的完整URL地址)
     * @param integer $type 网页授权模式(0静默模式,1高级授权)
     * @return array|boolean
     */
    public function oauth(string $sessid, string $source, int $type = 0): array
    {
        $fans = $this->app->cache->get("{$this->appid}_{$sessid}_fans", []);
        $token = $this->app->cache->get("{$this->appid}_{$sessid}_token", []);
        $openid = $this->app->cache->get("{$this->appid}_{$sessid}_openid", '');
        if (!empty($openid) && !empty($type) && !empty($fans)) {
            return ['openid' => $openid, 'token' => $token, 'fans' => $fans, 'url' => ''];
        }
        $mode = empty($type) ? 'snsapi_base' : 'snsapi_userinfo';
        $params = ['mode' => $type, 'sessid' => $sessid, 'enurl' => enbase64url($source)];
        $location = url('api.push/oauth', [], false, true)->build() . '?' . http_build_query($params);
        $oauthurl = AuthService::WeOpenService()->getOauthRedirect($this->appid, $location, $mode);
        return ['openid' => $openid, 'token' => $token, 'fans' => $fans, 'url' => $oauthurl];
    }

    /**
     * 微信网页JS签名
     * @param string $url 当前会话URL地址(需包含域名的完整URL地址)
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function jsSign(string $url): array
    {
        return AuthService::WeChatScript($this->appid)->getJsSign($url);
    }
}