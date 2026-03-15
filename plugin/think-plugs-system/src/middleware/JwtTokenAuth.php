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

namespace plugin\system\middleware;

use plugin\system\service\SystemAuthService;
use think\admin\runtime\RequestTokenService;
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
        RequestTokenService::capture($request);
        if ($token = RequestTokenService::systemToken($request)) {
            try {
                SystemAuthService::resolve($token, true);
            } catch (\Throwable $exception) {
                SystemAuthService::forget();
                RequestTokenService::forgetSystem($request);
            }
        } else {
            SystemAuthService::forget();
        }

        return $next($request);
    }
}
