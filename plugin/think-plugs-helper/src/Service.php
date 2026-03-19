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

use plugin\helper\command\Database;
use plugin\helper\command\DbBackupStruct;
use plugin\helper\command\DbIndexStruct;
use plugin\helper\command\DbMigrateStruct;
use plugin\helper\command\DbModelStruct;
use plugin\helper\command\DbRestoreStruct;
use plugin\helper\command\Package;
use plugin\helper\command\Publish;
use plugin\helper\command\Replace;
use plugin\helper\command\Sysmenu;

class Service extends \think\Service
{
    public function boot()
    {
        $this->commands([
            Publish::class,
            Package::class,
            Database::class,
            Replace::class,
            Sysmenu::class,
            DbMigrateStruct::class,
            DbModelStruct::class,
            DbIndexStruct::class,
            DbBackupStruct::class,
            DbRestoreStruct::class,
        ]);
    }
}
