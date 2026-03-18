<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
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

namespace plugin\account\service;

use plugin\wechat\client\service\WechatService;
use think\admin\Exception;
use think\admin\Service;

/**
 * 微信小程序接入服务
 * @class WxappService
 */
class WxappService extends Service
{
    private const SESSION_CACHE_PREFIX = 'plugin.account.wxapp.session';

    private const SESSION_EXPIRE = 7200;

    /**
     * 获取小程序配置参数.
     * @throws Exception
     */
    public function getConfig(): array
    {
        return WechatService::getWxconf();
    }

    /**
     * 获取小程序 APPID.
     * @throws Exception
     */
    public function getAppid(): string
    {
        return strval($this->getConfig()['appid'] ?? '');
    }

    /**
     * 获取小程序会话信息并缓存.
     * @throws Exception
     */
    public function getSession(string $code): array
    {
        $cacheKey = $this->buildSessionCacheKey($code);
        $cached = $this->app->cache->get($cacheKey, []);
        if (isset($cached['openid'], $cached['session_key'])) {
            return $cached;
        }

        $result = WechatService::WeMiniCrypt()->session($code);
        if (!isset($result['openid'], $result['session_key'])) {
            throw new Exception(strval($result['errmsg'] ?? '获取会话失败'));
        }

        $this->app->cache->set($cacheKey, $result, $this->resolveSessionExpire($result));
        return $result;
    }

    /**
     * 解密小程序数据.
     * @throws Exception
     */
    public function decode(string $iv, string $sessionKey, string $encrypted): array
    {
        $result = WechatService::WeMiniCrypt()->decode($iv, $sessionKey, $encrypted);
        if (!is_array($result)) {
            throw new Exception('解析失败');
        }

        return $result;
    }

    /**
     * 获取用户手机号.
     * @throws Exception
     */
    public function getPhoneNumber(string $code): array
    {
        $result = WechatService::WeMiniCrypt()->getPhoneNumber($code);
        if (!is_array($result)) {
            throw new Exception('解析失败');
        }

        return $result;
    }

    /**
     * 生成小程序码.
     */
    public function createMiniPath(string $path, int $size = 430): string
    {
        return WechatService::WeMiniQrcode()->createMiniPath($path, $size);
    }

    /**
     * 获取直播列表.
     */
    public function getLiveList(int $start = 0, int $limit = 10): array
    {
        $result = WechatService::WeMiniLive()->getLiveList($start, $limit);
        return is_array($result) ? $result : [];
    }

    /**
     * 获取直播回放信息.
     */
    public function getLiveInfo(array $data): array
    {
        $result = WechatService::WeMiniLive()->getLiveInfo($data);
        return is_array($result) ? $result : [];
    }

    /**
     * 构建 Session 缓存键.
     * @throws Exception
     */
    private function buildSessionCacheKey(string $code): string
    {
        return sprintf('%s:%s:%s', self::SESSION_CACHE_PREFIX, $this->getAppid() ?: '-', md5($code));
    }

    /**
     * 解析 Session 缓存时长.
     */
    private function resolveSessionExpire(array $result): int
    {
        $expire = intval($result['expires_in'] ?? self::SESSION_EXPIRE);
        return $expire > 0 ? $expire : self::SESSION_EXPIRE;
    }
}
