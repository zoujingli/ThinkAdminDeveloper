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

class InstallCenter20241010 extends Migrator
{

    /**
     * 获取脚本名称
     * @return string
     */
    public function getName(): string
    {
        return 'CenterPlugin';
    }

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
