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

namespace plugin\helper;

use plugin\helper\command\database\BackupCommand;
use plugin\helper\command\database\DatabaseCommand;
use plugin\helper\command\database\IndexCommand;
use plugin\helper\command\database\MigrateCommand;
use plugin\helper\command\database\ModelCommand;
use plugin\helper\command\database\ReplaceCommand;
use plugin\helper\command\database\RestoreCommand;
use plugin\helper\command\project\PackageCommand;
use plugin\helper\command\project\PublishCommand;
use plugin\helper\command\system\MenuResetCommand;

class Service extends \think\Service
{
    /**
     * 注册开发期与交付期工具命令。
     */
    public function boot()
    {
        $this->commands([
            PublishCommand::class,
            PackageCommand::class,
            DatabaseCommand::class,
            ReplaceCommand::class,
            MenuResetCommand::class,
            MigrateCommand::class,
            ModelCommand::class,
            IndexCommand::class,
            BackupCommand::class,
            RestoreCommand::class,
        ]);
    }
}
