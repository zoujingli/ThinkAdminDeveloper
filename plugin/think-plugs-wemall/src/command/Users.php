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

namespace plugin\wemall\command;

use plugin\account\model\PluginAccountUser;
use plugin\wemall\service\UserAgent;
use plugin\wemall\service\UserOrder;
use plugin\wemall\service\UserUpgrade;
use think\admin\Command;
use think\console\Input;
use think\console\Output;
use think\db\exception\DbException;

/**
 * 同步计算用户信息.
 * @class Users
 */
class Users extends Command
{
    /**
     * 指令参数配置.
     */
    public function configure()
    {
        $this->setName('xdata:mall:users')->setDescription('同步用户关联数据');
    }

    /**
     * 执行指令.
     * @throws \think\admin\Exception
     * @throws DbException
     */
    protected function execute(Input $input, Output $output)
    {
        [$total, $count] = [PluginAccountUser::mk()->count(), 0];
        foreach (PluginAccountUser::mk()->field('id')->order('id desc')->cursor() as $user) {
            try {
                $this->queue->message($total, ++$count, "刷新用户 [{$user['id']}] 数据...");
                UserUpgrade::upgrade(UserAgent::upgrade(UserOrder::entry(intval($user['id']))));
                UserUpgrade::recount(intval($user['id']), true);
                $this->queue->message($total, $count, "刷新用户 [{$user['id']}] 数据成功", 1);
            } catch (\Exception $exception) {
                $this->queue->message($total, $count, "刷新用户 [{$user['id']}] 数据失败, {$exception->getMessage()}", 1);
            }
        }
        $this->setQueueSuccess("此次共处理 {$total} 个刷新操作。");
        return 0;
    }
}
