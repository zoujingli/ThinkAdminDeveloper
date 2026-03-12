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

namespace think\admin\auth\middleware;

use think\admin\auth\AdminService;
use think\App;
use think\Request;
use think\Response;

/**
 * JWT 认证中间键.
 * @class JwtTokenAuth
 */
class JwtTokenAuth
{
    /**
     * 当前 App 对象
     * @var App
     */
    protected $app;

    /**
     * Construct.
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * 中间键处理.
     */
    public function handle(Request $request, \Closure $next): Response
    {
        if ($token = AdminService::requestToken($request)) {
            try {
                AdminService::resolve($token, true);
            } catch (\Throwable $exception) {
                AdminService::forget();
                $this->clearRequestToken($request);
            }
        } else {
            AdminService::forget();
            $this->clearRequestToken($request);
        }

        return $next($request);
    }

    /**
     * 清理当前请求中的认证头，避免同一请求链路再次解析旧令牌。
     */
    private function clearRequestToken(Request $request): void
    {
        $headers = $request->header();
        unset($headers['authorization'], $headers['Authorization']);
        $request->withHeader($headers);

        $server = $_SERVER;
        unset($server['HTTP_AUTHORIZATION'], $server['REDIRECT_HTTP_AUTHORIZATION']);
        $request->withServer($server);
    }
}
