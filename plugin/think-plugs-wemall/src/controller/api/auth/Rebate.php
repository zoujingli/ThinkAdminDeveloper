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

namespace plugin\wemall\controller\api\auth;

use plugin\wemall\controller\api\Auth;
use plugin\wemall\model\PluginWemallConfigLevel;
use plugin\wemall\model\PluginWemallUserRebate;
use plugin\wemall\service\UserRebate;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 代理返佣管理.
 * @class Rebate
 */
class Rebate extends Auth
{
    /**
     * 获取代理返佣记录.
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function get()
    {
        $query = PluginWemallUserRebate::mQuery();
        $query->where(['unid' => $this->unid]);
        $query->equal('type,status');
        $query->like('name|code|order_no#keys');
        $query->whereRaw('amount>0');
        $query->order('id desc');
        $this->success('获取返佣统计', $query->page(true, false, false, 15));
    }

    /**
     * 获取我的奖励.
     */
    public function prize()
    {
        [$map, $data] = [['number' => $this->levelCode], []];
        $prizes = PluginWemallUserRebate::mk()->group('name')->column('name');
        $rebate = PluginWemallConfigLevel::mk()->where($map)->value('rebate_rule', '');
        $codemap = array_merge($prizes, str2arr($rebate));
        foreach (UserRebate::prizes as $code => $prize) {
            if (in_array($code, $codemap)) {
                $data[$code] = $prize;
            }
        }
        $this->success('获取我的奖励', $data);
    }

    /**
     * 获取奖励配置.
     */
    public function prizes()
    {
        $this->success('获取系统奖励', array_values(UserRebate::prizes));
    }
}
