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

namespace plugin\worker;

use plugin\worker\command\Queue;
use plugin\worker\command\Worker;
use plugin\worker\service\ProcessService;
use plugin\worker\service\QueueService;
use think\admin\Library;
use think\admin\Plugin;
use think\admin\service\ProcessService as ProcessRuntime;
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
            ProcessRuntime::BIND_NAME => ProcessService::class,
            QueueRuntime::BIND_NAME => QueueService::class,
        ]);

        $this->commands([
            'xadmin:worker' => Worker::class,
            'xadmin:queue' => Queue::class,
        ]);
    }

    public static function menu(): array
    {
        return [];
    }
}
