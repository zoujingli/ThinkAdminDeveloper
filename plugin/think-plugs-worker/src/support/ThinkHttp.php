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
