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
use plugin\system\middleware\LoadModuleLangPack;
use plugin\system\middleware\RbacAccess;
use plugin\system\service\SystemContext as PluginSystemContext;
use think\admin\contract\SystemContextInterface;
use think\admin\Plugin;
use think\middleware\LoadLangPack;

/**
 * 插件服务注册.
 * @class Service
 */
class Service extends Plugin
{
    /**
     * 注册系统基础服务。
     */
    public function register(): void
    {
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
        $this->app->middleware->add(LoadLangPack::class);
        $this->app->middleware->add(LoadModuleLangPack::class);
        $this->app->middleware->add(RbacAccess::class, 'route');
    }
}
