<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wemall
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wemall
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wemall\controller\api\auth;

use plugin\wemall\controller\api\Auth;
use plugin\wemall\model\PluginWemallConfigLevel;
use plugin\wemall\model\PluginWemallUserRebate;
use plugin\wemall\service\UserRebate;

/**
 * 用户返佣管理
 * @class Rebate
 * @package plugin\wemall\controller\api\auth
 */
class Rebate extends Auth
{
    /**
     * 获取用户返佣记录
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function get()
    {
        $query = PluginWemallUserRebate::mQuery()->where(['unid' => $this->unid]);
        $query->equal('type,status')->like('name|code|order_no#keys')->whereRaw('amount>0');
        $this->success('获取返佣统计', $query->order('id desc')->page(true, false, false, 15));
    }

    /**
     * 获取我的奖励
     * @return void
     */
    public function prize()
    {
        [$map, $data] = [['number' => $this->levelCode], []];
        $prizes = PluginWemallUserRebate::mk()->group('name')->column('name');
        $rebate = PluginWemallConfigLevel::mk()->where($map)->value('rebate_rule', '');
        $codemap = array_merge($prizes, str2arr($rebate));
        foreach (UserRebate::prizes as $code => $prize) {
            if (in_array($code, $codemap)) $data[$code] = $prize;
        }
        $this->success('获取我的奖励', $data);
    }

    /**
     * 获取奖励配置
     * @return void
     */
    public function prizes()
    {
        $this->success('获取系统奖励', array_values(UserRebate::prizes));
    }
}