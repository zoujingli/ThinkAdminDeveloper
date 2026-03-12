<?php

declare(strict_types=1);

namespace plugin\storage;

use think\admin\Plugin;

class Service extends Plugin
{
    /**
     * 定义插件入口.
     * @var string
     */
    protected string $appCode = 'storage';

    /**
     * 定义插件访问前缀.
     * @var string
     */
    protected string $appPrefix = 'storage';

    /**
     * 定义插件名称.
     * @var string
     */
    protected string $appName = '存储中心';

    /**
     * 定义安装包名.
     * @var string
     */
    protected string $package = 'zoujingli/think-plugs-storage';

    public function register(): void
    {
        $this->app->config->set(include dirname(__DIR__) . '/stc/config/storage.php', 'think_plugs_storage');
    }

    /**
     * 定义插件菜单.
     */
    public static function menu(): array
    {
        return [[
            'name' => '系统配置',
            'subs' => [[
                'name' => '存储配置中心',
                'icon' => 'layui-icon layui-icon-upload-drag',
                'node' => 'storage/config/index',
            ]],
        ]];
    }
}
