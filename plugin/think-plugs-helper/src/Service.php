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
