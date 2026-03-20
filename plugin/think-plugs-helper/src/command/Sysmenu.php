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

namespace plugin\helper\command;

use plugin\system\model\SystemMenu;
use think\admin\Command;
use think\admin\Exception;
use think\admin\extend\ArrayTree;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 重置并清理系统菜单.
 * @class Sysmenu
 */
class Sysmenu extends Command
{
    /**
     * 指令任务配置.
     */
    public function configure()
    {
        $this->setName('xadmin:sysmenu');
        $this->setDescription('Clean and Reset System Menu Data for ThinkAdmin');
    }

    /**
     * 任务执行入口.
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function handle()
    {
        /** @var QueryHelper $query */
        $query = SystemMenu::mQuery();
        $query->where(['status' => 1]);
        $menus = $query->db()->order('sort desc,id asc')->select()->toArray();
        $total = count($menus);
        $count = 0;
        $query->empty();
        $this->setQueueMessage($total, 0, '开始重置系统菜单编号...');
        foreach (ArrayTree::arr2tree($menus) as $sub1) {
            $pid1 = $this->write($sub1);
            $this->setQueueMessage($total, ++$count, "重写1级菜单：{$sub1['title']}");
            if (!empty($sub1['sub'])) {
                foreach ($sub1['sub'] as $sub2) {
                    $pid2 = $this->write($sub2, $pid1);
                    $this->setQueueMessage($total, ++$count, "重写2级菜单：-> {$sub2['title']}");
                    if (!empty($sub2['sub'])) {
                        foreach ($sub2['sub'] as $sub3) {
                            $this->write($sub3, $pid2);
                            $this->setQueueMessage($total, ++$count, "重写3级菜单：-> -> {$sub3['title']}");
                        }
                    }
                }
            }
        }
        $this->setQueueMessage($total, $count, '完成重置系统菜单编号！');
    }

    /**
     * 写入单项菜单数据.
     * @param array $arr 单项菜单数据
     * @param mixed $pid 上级菜单编号
     * @return int|string
     */
    private function write(array $arr, $pid = 0)
    {
        return SystemMenu::mk()->insertGetId([
            'pid' => $pid,
            'url' => $arr['url'],
            'icon' => $arr['icon'],
            'node' => $arr['node'],
            'title' => $arr['title'],
            'params' => $arr['params'],
            'target' => $arr['target'],
        ]);
    }
}
