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
use plugin\center\Service;
use plugin\helper\service\PhinxExtend;
use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', '-1');

class SyncCenterMenu20260320 extends Migrator
{
    public function getName(): string
    {
        return 'CenterMenuSync20260320';
    }

    public function change(): void
    {
        PhinxExtend::writePluginMenu(Service::class, [], [], $this);
    }
}
