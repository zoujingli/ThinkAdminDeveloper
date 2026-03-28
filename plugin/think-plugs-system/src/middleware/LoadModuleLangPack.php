<?php

declare(strict_types=1);

namespace plugin\system\middleware;

use plugin\system\service\LangService;
use think\App;
use think\Request;
use think\Response;

/**
 * System 模块语言包加载中间件.
 * @class LoadModuleLangPack
 */
class LoadModuleLangPack
{
    public function __construct(private App $app)
    {
    }

    public function handle(Request $request, \Closure $next): Response
    {
        LangService::loadCurrent($this->app);
        return $next($request);
    }
}
