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

namespace plugin\center\controller;

use think\admin\Controller;
use think\admin\service\NodeService;

/**
 * Legacy route compatibility for center layout pages.
 * @class Layout
 */
class Layout extends Controller
{
    protected function initialize(): void
    {
        $this->request->setController('Index')->setAction('layout');
        $this->node = NodeService::getCurrent();
    }

    /**
     * @login true
     */
    public function index(string $encode = '')
    {
        return $this->forward($encode);
    }

    public function __call(string $method, array $args)
    {
        return $this->forward($method);
    }

    private function forward(string $encode = '')
    {
        $this->request->setController('Index')->setAction('layout');
        return $this->app->make(Index::class, [], true)->layout($encode);
    }
}
