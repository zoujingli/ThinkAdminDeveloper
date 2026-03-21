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

namespace plugin\system;

use plugin\system\middleware\JwtTokenAuth;
use plugin\system\middleware\RbacAccess;
use plugin\system\service\SystemContext as PluginSystemContext;
use think\admin\contract\SystemContextInterface;
use think\admin\Library;
use think\admin\Plugin;
use think\admin\runtime\RequestTokenService;
use think\middleware\LoadLangPack;

/**
 * 插件服务注册.
 * @class Service
 */
class Service extends Plugin
{
    /**
     * 定义插件入口.
     */
    protected string $appCode = 'system';

    /**
     * 定义插件访问前缀.
     */
    protected string $appPrefix = 'system';

    /**
     * 定义插件名称.
     */
    protected string $appName = '系统管理';

    /**
     * 定义安装包名.
     */
    protected string $package = 'zoujingli/think-plugs-system';

    /**
     * 注册系统基础服务。
     */
    public function register(): void
    {
        Library::load(__DIR__ . '/common.php');
        $this->app->bind(SystemContextInterface::class, PluginSystemContext::class);
    }

    /**
     * 启动插件服务.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        $this->app->middleware->add(JwtTokenAuth::class);
        $isapi = RequestTokenService::authorizationToken($this->app->request) !== '';
        $agent = preg_replace('|\s+|', '', $this->app->request->header('user-agent', ''));
        $isrpc = is_numeric(stripos($agent, 'think-admin-jsonrpc')) || is_numeric(stripos($agent, 'PHPYarRPC'));
        if (empty($isapi) && empty($isrpc)) {
            $this->app->middleware->add(LoadLangPack::class);
        }
        $this->app->middleware->add(RbacAccess::class, 'route');
    }

    }
