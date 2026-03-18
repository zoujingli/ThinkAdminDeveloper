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
        $context = RequestTokenService::capture($request);
        if (function_exists('worker_auth_should_debug') && worker_auth_should_debug($request->pathinfo(), $request->cookie(), $request->header())) {
            worker_auth_debug('system.jwt.capture', [
                'path' => $request->pathinfo(),
                'authorization' => worker_auth_token_snapshot($context->authorizationToken()),
                'system_cookie' => worker_auth_token_snapshot($context->systemCookieToken()),
                'system_request' => worker_auth_token_snapshot($context->systemRequestToken()),
            ]);
        }

        if ($token = RequestTokenService::systemToken($request)) {
            try {
                SystemAuthService::resolve($token, true);
                if (function_exists('worker_auth_should_debug') && worker_auth_should_debug($request->pathinfo(), $request->cookie(), $request->header())) {
                    worker_auth_debug('system.jwt.resolve.ok', [
                        'path' => $request->pathinfo(),
                        'user_id' => SystemAuthService::getUserId(),
                        'session_id' => SystemAuthService::currentSessionId(),
                        'token' => worker_auth_token_snapshot($token),
                    ]);
                }
            } catch (\Throwable $exception) {
                SystemAuthService::forget();
                RequestTokenService::forgetSystem($request);
                if (function_exists('worker_auth_should_debug') && worker_auth_should_debug($request->pathinfo(), $request->cookie(), $request->header())) {
                    worker_auth_debug('system.jwt.resolve.fail', [
                        'path' => $request->pathinfo(),
                        'token' => worker_auth_token_snapshot($token),
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        } else {
            SystemAuthService::forget();
            if (function_exists('worker_auth_should_debug') && worker_auth_should_debug($request->pathinfo(), $request->cookie(), $request->header())) {
                worker_auth_debug('system.jwt.missing', [
                    'path' => $request->pathinfo(),
                ]);
            }
        }

        return $next($request);
    }
}
