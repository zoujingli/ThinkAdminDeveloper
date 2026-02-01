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

namespace plugin\worker\support;

use think\Cookie;
use Workerman\Protocols\Http\Response;

/**
 * 自定义 Cookie.
 * @class ThinkCookie
 */
class ThinkCookie extends Cookie
{
    /** @var Response */
    protected $response;

    /**
     * 绑定响应对象
     */
    public function withWorkerResponse(): Response
    {
        $this->cookie = [];
        return $this->response = new Response();
    }

    /**
     * 保存 Cookie 数据.
     * @param string $name cookie名称
     * @param string $value cookie值
     * @param int $expire cookie过期时间
     * @param string $path 有效的服务器路径
     * @param string $domain 有效域名/子域名
     * @param bool $secure 是否仅仅通过HTTPS
     * @param bool $httponly 仅可通过HTTP访问
     * @param string $samesite 防止CSRF攻击和用户追踪
     */
    protected function saveCookie(string $name, string $value, int $expire, string $path, string $domain, bool $secure, bool $httponly, string $samesite): void
    {
        $this->response->cookie($name, $value, $expire ?: null, $path, $domain, $secure, $httponly, $samesite);
    }
}
