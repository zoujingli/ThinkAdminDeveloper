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

namespace plugin\wuma;

use plugin\wuma\command\Create;
use plugin\wuma\controller\Query;
use think\admin\Plugin;

/**
 * 插件注册服务
 * @class Service
 */
class Service extends Plugin
{
    protected string $appName = '防伪溯源';

    protected string $package = 'zoujingli/think-plugs-wuma';

    public function register(): void
    {
        $this->commands([Create::class]);
        // 注册全局防伪访问路由
        $this->app->route->any('<mode>/<code>!<verify><extra?>', Query::class . '@index')->pattern([
            'mode' => 'c|n|m', 'code' => '[0-9a-zA-Z]+', 'verify' => '[0-9]{4}', 'extra' => '.+',
        ]);
    }
}
