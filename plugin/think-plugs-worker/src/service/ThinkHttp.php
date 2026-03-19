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

namespace plugin\worker\service;

use think\Http;

/**
 * Worker-friendly Http dispatcher.
 * It keeps middleware and route definitions in memory for long-running workers.
 */
class ThinkHttp extends Http
{
    protected bool $middlewaresLoaded = false;

    protected bool $routesLoaded = false;

    public function warmup(): void
    {
        $this->initialize();
        $this->loadMiddleware();
        $this->loadRoutes();
    }

    public function resetRoutes(): void
    {
        $this->app->route->clear();
        $this->routesLoaded = false;
    }

    protected function loadMiddleware(): void
    {
        if ($this->middlewaresLoaded) {
            return;
        }

        parent::loadMiddleware();
        $this->middlewaresLoaded = true;
    }

    protected function loadRoutes(): void
    {
        if ($this->routesLoaded) {
            return;
        }

        parent::loadRoutes();
        $this->routesLoaded = true;
    }
}
