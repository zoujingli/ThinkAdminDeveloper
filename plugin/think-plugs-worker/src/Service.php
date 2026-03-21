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

namespace plugin\worker;

use plugin\worker\command\Queue;
use plugin\worker\command\Worker;
use plugin\worker\service\ProcessService;
use plugin\worker\service\QueueService;
use think\admin\Library;
use think\admin\Plugin;
use think\admin\service\QueueService as QueueRuntime;

/**
 * 插件注册服务
 * @class Service
 */
class Service extends Plugin
{
    /**
     * 定义插件名称.
     */
    protected string $appName = '运行时服务';

    /**
     * 定义安装包名.
     */
    protected string $package = 'zoujingli/think-plugs-worker';

    public function register()
    {
        Library::load(__DIR__ . '/common.php');
        $this->app->bind([
            ProcessService::BIND_NAME => ProcessService::class,
            QueueRuntime::BIND_NAME => QueueService::class,
        ]);

        $this->commands([
            'xadmin:worker' => Worker::class,
            'xadmin:queue' => Queue::class,
        ]);
    }
}
