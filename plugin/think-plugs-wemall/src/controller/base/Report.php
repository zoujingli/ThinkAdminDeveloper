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

namespace plugin\wemall\controller\base;

use plugin\payment\model\PluginPaymentBalance;
use plugin\wemall\model\PluginWemallConfigLevel;
use plugin\wemall\model\PluginWemallGoods;
use plugin\wemall\model\PluginWemallOrder;
use plugin\wemall\model\PluginWemallUserRebate;
use plugin\wemall\model\PluginWemallUserRelation;
use think\admin\Controller;
use think\Model;

/**
 * 商城数据统计
 * @class Report
 * @package plugin\wemall\controller\base
 */
class Report extends Controller
{
    /**
     * 显示数据统计
     * @auth true
     * @menu true
     * @throws \think\db\exception\DbException
     */
    public function index()
    {
        $this->usersTotal = PluginWemallUserRelation::mk()->cache(true, 60)->count();
        $this->goodsTotal = PluginWemallGoods::mk()->cache(true, 60)->where(['deleted' => 0])->count();
        $this->orderTotal = PluginWemallOrder::mk()->cache(true, 60)->whereRaw('status >= 4')->count();
        $this->amountTotal = PluginWemallOrder::mk()->cache(true, 60)->whereRaw('status >= 4')->sum('amount_total');

        // 近十天的用户及交易趋势
        if (empty($this->days = $this->app->cache->get('plugin.wemall.portals', []))) {
            $field = ['count(1)' => 'count', 'substr(create_time,1,10)' => 'mday'];
            // 统计用户数据
            $model = PluginWemallUserRelation::mk()->field($field);
            $users = $model->whereTime('create_time', '-10 days')->group('mday')->select()->column(null, 'mday');
            // 统计订单数据
            $model = PluginWemallOrder::mk()->field($field + ['sum(amount_total)' => 'amount']);
            $orders = $model->whereRaw('status>=4')->whereTime('create_time', '-10 days')->group('mday')->select()->column(null, 'mday');
            // 统计返佣数据
            $model = PluginWemallUserRebate::mk()->field($field + ['sum(amount)' => 'amount']);
            $rebates = $model->whereTime('create_time', '-10 days')->group('mday')->select()->column(null, 'mday');
            // 统计余额数据
            $model = PluginPaymentBalance::mk()->field($field + ['sum(case when amount>0 then amount else 0 end)' => 'amount1', 'sum(case when amount<0 then amount else 0 end)' => 'amount2']);
            $balances = $model->whereTime('create_time', '-10 days')->where(['deleted' => 0])->group('mday')->select()->column(null, 'mday');
            // 数据格式转换
            foreach ($users as &$user) $user = $user instanceof Model ? $user->toArray() : $user;
            foreach ($orders as &$order) $order = $order instanceof Model ? $order->toArray() : $order;
            foreach ($rebates as &$rebate) $rebate = $rebate instanceof Model ? $rebate->toArray() : $rebate;
            foreach ($balances as &$balance) $balance = $balance instanceof Model ? $balance->toArray() : $balance;
            // 组装15天的统计数据
            for ($i = 15; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i}days"));
                $this->days[] = [
                    '当天日期' => date('m-d', strtotime("-{$i}days")),
                    '增加用户' => ($users[$date] ?? [])['count'] ?? 0,
                    '订单数量' => ($orders[$date] ?? [])['count'] ?? 0,
                    '订单金额' => ($orders[$date] ?? [])['amount'] ?? 0,
                    '返佣金额' => ($rebates[$date] ?? [])['amount'] ?? 0,
                    '剩余余额' => PluginPaymentBalance::mk()->whereRaw("create_time<='{$date} 23:59:59' and deleted=0")->sum('amount'),
                    '充值余额' => ($balances[$date] ?? [])['amount1'] ?? 0,
                    '消费余额' => ($balances[$date] ?? [])['amount2'] ?? 0,
                ];
            }
            $this->app->cache->set('plugin.wemall.portals', $this->days, 60);
        }

        // 会员级别分布统计
        $levels = PluginWemallConfigLevel::mk()->where(['status' => 1])->order('number asc')->column('number code,name,0 count', 'number');
        foreach (PluginWemallUserRelation::mk()->field('count(1) count,level_code level')->group('level_code')->cursor() as $vo) {
            $levels[$vo['level']]['count'] = isset($levels[$vo['level']]) ? $vo['count'] : 0;
        }
        $this->levels = array_values($levels);
        $this->fetch();
    }
}