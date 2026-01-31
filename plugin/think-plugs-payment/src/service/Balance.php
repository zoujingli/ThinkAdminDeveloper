<?php

// +----------------------------------------------------------------------
// | Payment Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-payment
// | github 代码仓库：https://github.com/zoujingli/think-plugs-payment
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\payment\service;

use plugin\account\model\PluginAccountUser;
use plugin\payment\model\PluginPaymentBalance;
use think\admin\Exception;

/**
 * 用户余额调度器
 * @class Balance
 * @package plugin\payment\service
 */
abstract class Balance
{
    /**
     * 创建余额变更操作
     * @param integer $unid 账号编号
     * @param string $code 交易标识
     * @param string $name 交易标题
     * @param float $amount 变更金额
     * @param string $remark 变更描述
     * @param boolean $unlock 解锁状态
     * @return PluginPaymentBalance
     * @throws \think\admin\Exception
     */
    public static function create(int $unid, string $code, string $name, float $amount, string $remark = '', bool $unlock = false): PluginPaymentBalance
    {
        $user = PluginAccountUser::mk()->findOrEmpty($unid);
        if ($user->isEmpty()) throw new Exception('账号不存在！');

        // 扣减余额检查
        $map = ['unid' => $unid, 'cancel' => 0, 'deleted' => 0];
        $usable = PluginPaymentBalance::mk()->where($map)->sum('amount');
        if ($amount < 0 && abs($amount) > $usable) throw new Exception('扣减余额不足！');

        // 余额标准字段
        $data = ['unid' => $unid, 'code' => $code, 'name' => $name, 'amount' => strval($amount), 'remark' => $remark];

        // 锁定状态处理
        $data['unlock'] = intval($unlock);
        if ($data['unlock']) $data['unlock_time'] = date('Y-m-d H:i:s');

        // 统计操作前的金额
        $data['amount_prev'] = $usable;
        $data['amount_next'] = round($usable + $amount, 2);

        // 检查编号是否重复
        $map = ['unid' => $unid, 'code' => $code, 'deleted' => 0];
        $model = PluginPaymentBalance::mk()->where($map)->findOrEmpty();

        // 更新或写入余额变更
        if ($model->save($data)) {
            self::recount($unid);
            return $model->refresh();
        } else {
            throw new Exception('余额变更失败！');
        }
    }

    /**
     * 解锁余额变更操作
     * @param string $code 交易订单
     * @param integer $unlock 锁定状态
     * @return PluginPaymentBalance
     * @throws \think\admin\Exception
     */
    public static function unlock(string $code, int $unlock = 1): PluginPaymentBalance
    {
        return self::set($code, ['unlock' => $unlock, 'unlock_time' => date('Y-m-d H:i:s')]);
    }

    /**
     * 作废余额变更操作
     * @param string $code 交易订单
     * @param integer $cancel 取消状态
     * @return PluginPaymentBalance
     * @throws \think\admin\Exception
     */
    public static function cancel(string $code, int $cancel = 1): PluginPaymentBalance
    {
        return self::set($code, ['cancel' => $cancel, 'cancel_time' => date('Y-m-d H:i:s')]);
    }

    /**
     * 删除余额记录
     * @param string $code
     * @return PluginPaymentBalance
     * @throws \think\admin\Exception
     */
    public static function remove(string $code): PluginPaymentBalance
    {
        return self::set($code, ['deleted' => 1, 'deleted_time' => date('Y-m-d H:i:s')]);
    }

    /**
     * 刷新用户余额
     * @param integer $unid 指定用户编号
     * @param array|null &$data 非数组时更新数据
     * @return array [lock,used,total,usable]
     * @throws \think\admin\Exception
     */
    public static function recount(int $unid, ?array &$data = null): array
    {
        $isUpdate = !is_array($data);
        if ($isUpdate) $data = [];

        if ($isUpdate) {
            $user = PluginAccountUser::mk()->findOrEmpty($unid);
            if ($user->isEmpty()) throw new Exception('账号不存在！');
        }

        // 统计用户余额数据
        $map = ['unid' => $unid, 'cancel' => 0, 'deleted' => 0];
        $lock = PluginPaymentBalance::mk()->where($map)->where('unlock', '=', '0')->sum('amount');
        $used = PluginPaymentBalance::mk()->where($map)->where('amount', '<', '0')->sum('amount');
        $total = PluginPaymentBalance::mk()->where($map)->where('amount', '>', '0')->sum('amount');

        // 更新余额统计
        $data['balance_lock'] = strval($lock);
        $data['balance_used'] = bcmul(strval($used), '-1', 2);
        $data['balance_total'] = strval($total);
        $data['balance_usable'] = bcsub($data['balance_total'], $data['balance_used'], 2);
        if ($isUpdate) $user->save(['extra' => array_merge($user->getAttr('extra'), $data)]);
        return ['lock' => $lock, 'used' => abs($used), 'total' => $total, 'usable' => $data['balance_usable']];
    }

    /**
     * 获取余额模型
     * @param string $code
     * @return PluginPaymentBalance
     * @throws \think\admin\Exception
     */
    public static function get(string $code): PluginPaymentBalance
    {
        $map = ['code' => $code, 'deleted' => 0];
        $model = PluginPaymentBalance::mk()->where($map)->findOrEmpty();
        if ($model->isEmpty()) throw new Exception('无效的操作编号！');
        return $model;
    }

    /**
     * 更新余额记录
     * @param string $code
     * @param array $data
     * @return PluginPaymentBalance
     * @throws \think\admin\Exception
     */
    public static function set(string $code, array $data): PluginPaymentBalance
    {
        ($model = self::get($code))->save($data);
        self::recount($model->getAttr('unid'));
        return $model->refresh();
    }
}