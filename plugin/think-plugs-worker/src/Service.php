<?php

// +----------------------------------------------------------------------
// | Worker Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-worker
// | github 代码仓库：https://github.com/zoujingli/think-plugs-worker
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\worker;

use plugin\worker\command\Worker;
use think\admin\Plugin;

class Service extends Plugin
{
    /**
     * 定义插件名称
     * @var string
     */
    protected $appName = '网络服务';

    /**
     * 定义安装包名
     * @var string
     */
    protected $package = 'zoujingli/think-plugs-worker';

    public function register()
    {
        $this->commands(['xadmin:worker' => Worker::class]);
    }

    public static function menu(): array
    {
        return [];
    }
}