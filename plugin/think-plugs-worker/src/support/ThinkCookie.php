<?php

// +----------------------------------------------------------------------
// | Worker Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-worker
// | github 代码仓库：https://github.com/zoujingli/think-plugs-worker
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\worker\support;

use think\Cookie;
use Workerman\Protocols\Http\Response;

/**
 * 自定义 Cookie
 * @class ThinkCookie
 * @package plugin\worker\support
 */
class ThinkCookie extends Cookie
{
    /** @var Response */
    protected $response;

    /**
     * 绑定响应对象
     * @return Response
     */
    public function withWorkerResponse(): Response
    {
        $this->cookie = [];
        return $this->response = new Response();
    }

    /**
     * 保存 Cookie 数据
     * @param string $name cookie名称
     * @param string $value cookie值
     * @param integer $expire cookie过期时间
     * @param string $path 有效的服务器路径
     * @param string $domain 有效域名/子域名
     * @param boolean $secure 是否仅仅通过HTTPS
     * @param boolean $httponly 仅可通过HTTP访问
     * @param string $samesite 防止CSRF攻击和用户追踪
     * @return void
     */
    protected function saveCookie(string $name, string $value, int $expire, string $path, string $domain, bool $secure, bool $httponly, string $samesite): void
    {
        $this->response->cookie($name, $value, $expire ?: null, $path, $domain, $secure, $httponly, $samesite);
    }
}