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

namespace plugin\system\middleware;

use plugin\system\service\SystemAuthService;
use think\App;
use think\exception\HttpResponseException;
use think\Request;
use think\Response;

/**
 * 后台权限中间键.
 * @class RbacAccess
 */
class RbacAccess
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
        $langSet = $this->app->lang->getLangSet();
        if (is_file($file = dirname(__DIR__, 2) . "/lang/{$langSet}.php")) {
            $this->app->lang->load($file, $langSet);
        }

        if (is_file($file = syspath("lang/{$langSet}.php"))) {
            $this->app->lang->load($file, $langSet);
        }

        $ignore = $this->app->config->get('app.rbac_ignore', []);
        if (in_array($this->app->http->getName(), $ignore) || SystemAuthService::check()) {
            return $next($request);
        }

        if (SystemAuthService::isLogin()) {
            if (function_exists('worker_auth_should_debug') && worker_auth_should_debug($request->pathinfo(), $request->cookie(), $request->header())) {
                worker_auth_debug('system.rbac.denied', [
                    'path' => $request->pathinfo(),
                    'user_id' => SystemAuthService::getUserId(),
                    'login' => true,
                    'reason' => 'forbidden',
                ]);
            }
            throw new HttpResponseException(json(['code' => 0, 'info' => lang('禁用访问！')]));
        }

        $loginUrl = $this->app->config->get('app.rbac_login') ?: 'system/login/index';
        $loginPage = preg_match('#^(/|https?://)#', $loginUrl) ? $loginUrl : sysuri($loginUrl);
        if (function_exists('worker_auth_should_debug') && worker_auth_should_debug($request->pathinfo(), $request->cookie(), $request->header())) {
            worker_auth_debug('system.rbac.relogin', [
                'path' => $request->pathinfo(),
                'login' => false,
                'redirect' => $loginPage,
            ]);
        }
        throw new HttpResponseException(json(['code' => 0, 'info' => lang('请重新登录！'), 'url' => $loginPage]));
    }
}
