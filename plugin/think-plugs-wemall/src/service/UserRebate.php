<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | Payment Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | 版权所有 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | 官方网站: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | 开源协议 ( https://mit-license.org )
 * | 免责声明 ( https://thinkadmin.top/disclaimer )
 * | 会员特权 ( https://thinkadmin.top/vip-introduce )
 * +----------------------------------------------------------------------
 * | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
 * | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace plugin\wemall\service;

use plugin\account\model\PluginAccountUser;
use plugin\wemall\model\PluginWemallConfigDiscount;
use plugin\wemall\model\PluginWemallConfigLevel;
use plugin\wemall\model\PluginWemallConfigRebate;
use plugin\wemall\model\PluginWemallOrder;
use plugin\wemall\model\PluginWemallUserRebate;
use plugin\wemall\model\PluginWemallUserRelation;
use plugin\wemall\model\PluginWemallUserTransfer;
use think\admin\Exception;
use think\admin\extend\CodeExtend;
use think\admin\Library;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 实时发放订单返佣服务
 * @class UserRebate
 */
abstract class UserRebate
{
    public const pEqual = 'equal';

    public const pOrder = 'order';

    public const pFirst = 'first';

    public const pRepeat = 'repeat';

    public const pUpgrade = 'upgrade';

    // 奖励名称配置
    public const prizes = [
        self::pOrder => '下单奖励',
        self::pFirst => '首购奖励',
        self::pRepeat => '复购奖励',
        self::pUpgrade => '升级奖励',
        self::pEqual => '平推返佣',
    ];

    /**
     * 用户编号.
     * @var int
     */
    protected static $unid;

    /**
     * 用户数据.
     * @var array
     */
    protected static $user;

    /**
     * 用户关系.
     * @var array
     */
    protected static $rela0;

    /**
     * 直接代理.
     * @var array
     */
    protected static $rela1;

    /**
     * 间接代理.
     * @var array
     */
    protected static $rela2;

    /**
     * 间接2代理.
     * @var array
     */
    protected static $rela3;

    /**
     * 订单数据.
     * @var array
     */
    protected static $order;

    /**
     * 到账时间.
     * @var int
     */
    protected static $status = 0;

    /**
     * 当前执行配置.
     * @var array
     */
    protected static $config = [];

    /**
     * 执行订单返佣处理.
     * @param PluginWemallOrder|string $order
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function create($order)
    {
        // 获取订单数据
        self::$order = UserOrder::widthOrder($order)->toArray();
        if (empty(self::$order) || empty(self::$order['payment_status'])) {
            throw new Exception('订单不存在');
        }
        // 订单总金额
        if (bccomp(strval(self::$order['amount_total']), '0.00', 2) <= 0) {
            throw new Exception('订单金额为零');
        }
        if (bccomp(strval(self::$order['rebate_amount']), '0.00', 2) <= 0) {
            throw new Exception('订单返佣为零');
        }

        // 获取用户数据
        self::$unid = intval(self::$order['unid']);
        self::$user = PluginAccountUser::mk()->findOrEmpty(self::$unid)->toArray();
        self::$rela0 = PluginWemallUserRelation::mk()->where(['unid' => self::$unid])->findOrEmpty()->toArray();
        if (empty(self::$user) || empty(self::$rela0)) {
            throw new Exception('用户不存在');
        }

        // 获取上一级代理数据
        if (self::$order['puid1'] > 0) {
            $map = ['unid' => self::$order['puid1']];
            self::$rela1 = PluginWemallUserRelation::mk()->where($map)->findOrEmpty()->toArray();
            if (empty(self::$rela1)) {
                throw new Exception('直接代理不存在');
            }
        }

        // 获取上二级代理数据
        if (self::$order['puid2'] > 0) {
            $map = ['unid' => self::$order['puid2']];
            self::$rela2 = PluginWemallUserRelation::mk()->where($map)->findOrEmpty()->toArray();
            if (empty(self::$rela2)) {
                throw new Exception('上二代理不存在');
            }
        }

        // 获取上三级代理数据
        if (self::$order['puid3'] > 0) {
            $map = ['unid' => self::$order['puid3']];
            self::$rela3 = PluginWemallUserRelation::mk()->where($map)->findOrEmpty()->toArray();
            if (empty(self::$rela3)) {
                throw new Exception('上三代理不存在');
            }
        }

        // 批量查询规则并发放奖励
        $where = ['status' => 1, 'deleted' => 0];
        PluginWemallConfigRebate::mk()->where($where)->order('sort desc,id desc')->select()->map(function (PluginWemallConfigRebate $item) {
            self::$config = $item->toArray();
            // 返利结算时间
            self::$status = empty(self::$config['stype']) ? 1 : 0;
            // 检查关系链无代理的情况
            if (self::$config['p1_level'] < -1 && !empty(self::$rela1)) {
                return;
            }
            if (self::$config['p2_level'] < -1 && !empty(self::$rela2)) {
                return;
            }
            if (self::$config['p3_level'] < -1 && !empty(self::$rela3)) {
                return;
            }
            // 检查关系链任何代理情况
            if (self::$config['p1_level'] == -1 && empty(self::$rela1)) {
                return;
            }
            if (self::$config['p2_level'] == -1 && empty(self::$rela2)) {
                return;
            }
            if (self::$config['p3_level'] == -1 && empty(self::$rela3)) {
                return;
            }
            // 检查关系链代理等级匹配
            if (self::$config['p0_level'] > -1 && (empty(self::$rela0) || self::$rela0['level_code'] !== self::$config['p0_level'])) {
                return;
            }
            if (self::$config['p1_level'] > -1 && (empty(self::$rela1) || self::$rela1['level_code'] !== self::$config['p1_level'])) {
                return;
            }
            if (self::$config['p2_level'] > -1 && (empty(self::$rela2) || self::$rela2['level_code'] !== self::$config['p2_level'])) {
                return;
            }
            if (self::$config['p3_level'] > -1 && (empty(self::$rela3) || self::$rela3['level_code'] !== self::$config['p3_level'])) {
                return;
            }
            // 调用对应接口发放奖励
            if (method_exists(self::class, $method = sprintf('_%s', self::$config['type']))) {
                $logVar = [self::$order['order_no'], self::$config['code'], self::$config['name']];
                Library::$sapp->log->notice(sprintf('订单 %s 开始发放 %s#[%s] 奖励', ...$logVar));
                foreach ([self::$rela0, self::$rela1, self::$rela2, self::$rela3] as $k => $v) {
                    if ($v) {
                        self::$method($k, $v);
                    }
                }
                Library::$sapp->log->notice(sprintf('订单 %s 完成发放 %s#[%s] 奖励', ...$logVar));
            }
        });
    }

    /**
     * 确认收货订单返佣.
     * @param PluginWemallOrder|string $order
     * @throws Exception
     */
    public static function confirm($order): bool
    {
        $order = UserOrder::widthOrder($order);
        if ($order->isEmpty() || $order->getAttr('status') < 6) {
            throw new Exception('订单状态异常！');
        }
        /** @var PluginWemallUserRebate $item */
        $map = [['status', '=', 0], ['deleted', '=', 0], ['order_no', 'like', "{$order->getAttr('order_no')}%"]];
        foreach (PluginWemallUserRebate::mk()->where($map)->cursor() as $item) {
            $item->save(['status' => 1, 'remark' => '订单已确认收货！', 'confirm_time' => date('Y-m-d H:i:s')]);
            UserRebate::recount($item->getAttr('unid'));
        }
        return true;
    }

    /**
     * 取消订单发放返佣.
     * @param PluginWemallOrder|string $order
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function cancel($order): bool
    {
        $order = UserOrder::widthOrder($order);
        if ($order->isEmpty()) {
            throw new Exception('订单状态异常！');
        }
        // 更新返佣记录
        $map = [['deleted', '=', 0], ['order_no', 'like', "{$order->getAttr('order_no')}%"]];
        foreach (PluginWemallUserRebate::mk()->where($map)->cursor() as $item) {
            $item->save(['status' => 0, 'deleted' => 1, 'remark' => '订单已取消退回返佣！']);
            UserRebate::recount($item->getAttr('unid'));
        }
        return true;
    }

    /**
     * 同步刷新代理返佣.
     * @param int $unid 指定用户ID
     * @param null|array $data 非数组时更新数据
     * @param mixed $where 其他查询条件
     * @return array [total, used, lock, usable]
     */
    public static function recount(int $unid, ?array &$data = null, $where = []): array
    {
        if ($isUpdate = !is_array($data)) {
            $data = [];
        }
        if ($unid > 0) {
            $total = PluginWemallUserRebate::mk()->where($where)->whereRaw("unid='{$unid}' and deleted=0")->sum('amount');
            $count = PluginWemallUserTransfer::mk()->where($where)->whereRaw("unid='{$unid}' and status>0")->sum('amount');
            $locks = PluginWemallUserRebate::mk()->where($where)->whereRaw("unid='{$unid}' and status=0 and deleted=0")->sum('amount');
            $usable = bcsub(bcsub(strval($total), strval($count), 2), strval($locks), 2);
            [$data['rebate_total'], $data['rebate_used'], $data['rebate_lock'], $data['rebate_usable']] = [$total, $count, $locks, $usable];
            if ($isUpdate && ($user = PluginAccountUser::mk()->findOrEmpty($unid))->isExists()) {
                $user->save(['extra' => array_merge($user->getAttr('extra'), $data)]);
            }
        } else {
            $total = PluginWemallUserRebate::mk()->where($where)->whereRaw('deleted=0')->sum('amount');
            $count = PluginWemallUserTransfer::mk()->where($where)->whereRaw('status>0')->sum('amount');
            $locks = PluginWemallUserRebate::mk()->where($where)->whereRaw('status=0 and deleted=0')->sum('amount');
            $usable = bcsub(bcsub(strval($total), strval($count), 2), strval($locks), 2);
            [$data['rebate_total'], $data['rebate_used'], $data['rebate_lock'], $data['rebate_usable']] = [$total, $count, $locks, $usable];
        }
        return [$total, $count, $locks, $usable];
    }

    /**
     * 获取等级佣金描述.
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function levels(): array
    {
        // 解析商品折扣规则
        $discs = [];
        foreach (PluginWemallConfigDiscount::items() as $v) {
            foreach ($v['items'] as $vv) {
                $discs[$vv['level']][] = strval($vv['discount']);
            }
        }
        // 合并等级折扣及奖励
        $levels = PluginWemallConfigLevel::items(null);
        foreach ($levels as &$level) {
            $level['prizes'] = [];
            if (($disc = round(min($discs[$level['number']] ?? [100]))) < 100) {
                $level['prizes'][] = [
                    'type' => 0, 'value' => $disc, 'name' => '享折扣价', 'desc' => "最高可享受商品的 {$disc}% 折扣价购买~",
                ];
            }
        }
        return array_values($levels);
    }

    /**
     * 下单支付奖励.
     */
    protected static function _order(int $layer, array $relation): bool
    {
        $config = self::$config;
        // 检查返利是否已经发放
        if (empty($relation)) {
            return false;
        }
        $ono = self::$order['order_no'];
        $map = ['hash' => md5("{$config['code']}#{$ono}#{$relation['unid']}#{$config['type']}")];
        if (PluginWemallUserRebate::mk()->where($map)->findOrEmpty()->isExists()) {
            return false;
        }
        // 根据配置计算返利数据
        $value = $config["p{$layer}_reward_number"] ?: '0.000000';
        if ($config["p{$layer}_reward_type"] == 0) {
            $val = bcmul($value, '1', 2);
            $name = sprintf('%s，每单 %s 元', $config['name'], $val);
        } elseif ($config["p{$layer}_reward_type"] == 1) {
            $val = bcmul($value, self::$order['rebate_amount'], 2);
            $val = bcdiv($val, '100', 2);
            $name = sprintf('%s，订单金额 %s%%', $config['name'], $value);
        } elseif ($config["p{$layer}_reward_type"] == 2) {
            $val = bcmul($value, self::$order['amount_profit'], 2);
            $val = bcdiv($val, '100', 2);
            $name = sprintf('%s，分佣金额 %s%%', $config['name'], $value);
        } else {
            return false;
        }
        // 写入返佣记录
        return self::wRebate($relation['unid'], $map, $name, $val, $layer);
    }

    /**
     * 用户首购奖励.
     */
    protected static function _first(int $layer, array $relation): bool
    {
        // 是否首次购买
        $orders = PluginWemallUserRebate::mk()->where(['order_unid' => self::$unid, 'status' => 1])->limit(2)->column('order_no');
        if (count($orders) > 1 || (count($orders) === 1 && !in_array(self::$order['order_no'], $orders))) {
            return false;
        }
        // 发放用户首推奖励
        return self::_order($layer, $relation);
    }

    /**
     * 用户复购奖励.
     */
    protected static function _repeat(int $layer, array $relation): bool
    {
        // 是否复购购买
        $orders = PluginWemallUserRebate::mk()->where(['order_unid' => self::$unid, 'status' => 1])->limit(2)->column('order_no');
        if (count($orders) < 1 || (count($orders) === 1 && in_array(self::$order['order_no'], $orders))) {
            return false;
        }
        // 发放用户复购奖励
        return self::_order($layer, $relation);
    }

    /**
     * 用户平推奖励发放.
     */
    protected static function _equal(int $layer, array $relation): bool
    {
        if (self::$rela0['level_code'] !== self::$rela1['level_code']) {
            return false;
        }
        return self::_order($layer, $relation);
    }

    /**
     * 用户升级奖励发放.
     */
    private static function _upgrade(int $layer, array $relation): bool
    {
        if (empty(self::$rela1)) {
            return false;
        }
        if (empty(self::$user['extra']['level_order']) || self::$user['extra']['level_order'] !== self::$order['order_no']) {
            return false;
        }
        return self::_order($layer, $relation);
    }

    /**
     * 写入返佣记录.
     * @param int $unid 奖励用户
     * @param array $map 查询条件
     * @param string $name 奖励名称
     * @param float $amount 奖励金额
     * @param int $layer 发放序号
     */
    private static function wRebate(int $unid, array $map, string $name, string $amount, int $layer = 0): bool
    {
        $rebate = PluginWemallUserRebate::mk()->where($map)->findOrEmpty();
        if ($rebate->isExists()) {
            return false;
        }
        if (self::$status) {
            $map['confirm_time'] = formatdate(self::$order['payment_time']);
        }
        return $rebate->save(array_merge([
            'unid' => $unid,
            'type' => self::$config['type'] ?? '',
            'date' => date('Y-m-d'),
            'code' => CodeExtend::uniqidDate(16, 'R'),
            'name' => $name,
            'layer' => $layer,
            'amount' => $amount,
            'status' => self::$status,
            'order_no' => self::$order['order_no'],
            'order_unid' => self::$order['unid'],
            'order_amount' => self::$order['amount_total'],
        ], $map));
    }
}
