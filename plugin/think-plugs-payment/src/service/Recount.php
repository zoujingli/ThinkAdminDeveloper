<?php

// +----------------------------------------------------------------------
// | Payment Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员特权 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-account
// | github 代码仓库：https://github.com/zoujingli/think-plugs-account
// +----------------------------------------------------------------------

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

namespace plugin\payment\service;

use plugin\account\model\PluginAccountUser;
use plugin\payment\service\Balance as BalanceAlias;
use plugin\payment\service\Integral as IntegralAlias;
use plugin\worker\service\Queue;
use think\db\exception\DbException;

/**
 * 刷新用户余额和积分.
 * @class Recount
 */
class Recount extends Queue
{
    /**
     * @throws \think\admin\Exception
     * @throws DbException
     */
    public function execute(array $data = [])
    {
        $this->balance()->setQueueSuccess(lang('刷新用户余额及积分完成！'));
    }

    /**
     * 刷新用户余额.
     * @return static
     * @throws \think\admin\Exception
     * @throws DbException
     */
    private function balance(): Recount
    {
        [$total, $count] = [PluginAccountUser::mk()->count(), 0];
        foreach (PluginAccountUser::mk()->field('id,username,nickname,email')->cursor() as $user) {
            try {
                $nick = strval($user['username'] ?: ($user['nickname'] ?: $user['email']));
                $this->setQueueMessage($total, ++$count, lang('开始刷新用户 [%s %s] 余额及积分', [strval($user['id']), $nick]));
                BalanceAlias::recount(intval($user['id'])) && IntegralAlias::recount(intval($user['id']));
                $this->setQueueMessage($total, $count, lang('刷新用户 [%s %s] 余额及积分', [strval($user['id']), $nick]), 1);
            } catch (\Exception $exception) {
                $this->setQueueMessage($total, $count, lang('刷新用户 [%s %s] 余额及积分失败, %s', [strval($user['id']), $nick, $exception->getMessage()]), 1);
            }
        }
        return $this;
    }
}
