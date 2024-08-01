<?php

// +----------------------------------------------------------------------
// | Payment Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-account
// | github 代码仓库：https://github.com/zoujingli/think-plugs-account
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\payment\queue;

use plugin\account\model\PluginAccountUser;
use plugin\payment\service\Balance as BalanceAlias;
use plugin\payment\service\Integral as IntegralAlias;
use think\admin\Queue;

/**
 * 刷新用户余额和积分
 * @class Recount
 * @package think\admin\Queue
 */
class Recount extends Queue
{
    /**
     * @param array $data
     * @return void
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DbException
     */
    public function execute(array $data = [])
    {
        $this->balance()->setQueueSuccess('刷新用户余额及积分完成！');
    }

    /**
     * 刷新用户余额
     * @return static
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DbException
     */
    private function balance(): Recount
    {
        [$total, $count] = [PluginAccountUser::mk()->count(), 0];
        foreach (PluginAccountUser::mk()->field('id')->cursor() as $user) try {
            $nick = $user['username'] ?: ($user['nickname'] ?: $user['email']);
            $this->setQueueMessage($total, ++$count, "开始刷新用户 [{$user['id']} {$nick}] 余额及积分");
            BalanceAlias::recount(intval($user['id'])) && IntegralAlias::recount(intval($user['id']));
            $this->setQueueMessage($total, $count, "刷新用户 [{$user['id']} {$nick}] 余额及积分", 1);
        } catch (\Exception $exception) {
            $this->setQueueMessage($total, $count, "刷新用户 [{$user['id']} {$nick}] 余额及积分失败, {$exception->getMessage()}", 1);
        }
        return $this;
    }
}