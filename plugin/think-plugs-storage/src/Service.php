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

namespace plugin\storage;

use think\admin\Plugin;

class Service extends Plugin
{
    /**
     * 定义插件入口.
     */
    protected string $appCode = 'storage';

    /**
     * 定义插件访问前缀.
     */
    protected string $appPrefix = 'storage';

    /**
     * 定义插件名称.
     */
    protected string $appName = '存储中心';

    /**
     * 定义安装包名.
     */
    protected string $package = 'zoujingli/think-plugs-storage';

    public function register(): void
    {
        $this->app->config->set(include dirname(__DIR__) . '/stc/config/storage.php', 'think_plugs_storage');
    }

    }
