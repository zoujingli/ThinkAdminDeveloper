<?php

// +----------------------------------------------------------------------
// | Center Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2024 Anyon <zoujingli@qq.com>
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-center
// +----------------------------------------------------------------------

use think\admin\extend\PhinxExtend;
use think\migration\Migrator;

class InstallCenterData extends Migrator
{
    public function change()
    {
        set_time_limit(0);
        @ini_set('memory_limit', -1);
        $this->insertMenu();
    }

    private function insertMenu()
    {
        PhinxExtend::write2menu([
            [
                'name' => '插件入口',
                'sort' => '999',
                'node' => "plugin-center/index/index",
            ],
        ], [
            'url|node' => "plugin-center/index/index"
        ]);
    }
}
