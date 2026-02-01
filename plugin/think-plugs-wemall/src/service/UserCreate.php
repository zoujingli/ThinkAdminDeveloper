<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
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
use plugin\account\service\Account;
use plugin\wemall\model\PluginWemallUserCreate;
use plugin\wemall\model\PluginWemallUserRebate;
use plugin\wemall\model\PluginWemallUserRelation;
use plugin\wemall\model\PluginWemallUserTransfer;
use think\admin\Exception;
use think\admin\extend\CodeExtend;
use think\admin\Library;

/**
 * 用户账号管理.
 * @class UserCreate
 */
abstract class UserCreate
{
    /**
     * 创建账号及返佣.
     * @param int|PluginWemallUserCreate|string $user
     * @throws Exception
     */
    public static function create($user)
    {
        if (($user = self::withModel($user))->isExists()) {
            try {
                $data = $user->hidden(['id', 'create_time', 'update_time'])->toArray();
                Library::$sapp->db->transaction(function () use ($user, $data) {
                    // 检查代理权限
                    if (!empty($data['agent_phone'])) {
                        $where = ['phone' => $data['agent_phone'], 'deleted' => 0];
                        $parent = PluginAccountUser::mk()->where($where)->findOrEmpty();
                        $relation = PluginWemallUserRelation::mk()->where(['unid' => $parent->getAttr('id')])->findOrEmpty();
                        if ($parent->isEmpty() || $relation->isEmpty()) {
                            throw new Exception('无效推荐人！');
                        }
                        if (empty($relation->getAttr('entry_agent'))) {
                            throw new Exception('上级无权限！');
                        }
                    }
                    // 检查并创建账号
                    $inset = ['phone' => $data['phone'], 'headimg' => $data['headimg'], 'nickname' => $data['name'], 'deleted' => 0];
                    ($account = Account::mk(Account::WAP, $inset))->isNull() && $account->set($inset);
                    $account->isBind() || $account->bind($inset, $data) && $account->pwdModify($data['password']);
                    // 绑定上级代理身份
                    if (isset($parent)) {
                        UserUpgrade::bindAgent($account->getUnid(), intval($parent->getAttr('id')));
                    }
                    // 创建返佣记录及提现记录
                    $map = ['code' => $data['rebate_total_code'] ?: CodeExtend::uniqidDate(16, 'R'), 'unid' => $account->getUnid()];
                    ($rebate = PluginWemallUserRebate::mk()->where($map)->findOrEmpty())->save([
                        'unid' => $account->getUnid(),
                        'code' => $map['code'],
                        'hash' => md5($map['code']),
                        'date' => date('Y-m-d'),
                        'type' => 'platform',
                        'name' => '初始化累计佣金',
                        'remark' => $user->getAttr('rebate_total_desc'),
                        'amount' => strval($user->getAttr('rebate_total')),
                        'order_no' => '',
                        'order_amount' => '0.00',
                        'confirm_time' => date('Y-m-d H:i:s'),
                    ]);
                    // 创建提现记录
                    $map = ['code' => $user->getAttr('rebate_usable_code') ?: CodeExtend::uniqidDate(16, 'T'), 'unid' => $account->getUnid()];
                    ($transfer = PluginWemallUserTransfer::mk()->where($map)->findOrEmpty())->save([
                        'unid' => $account->getUnid(),
                        'type' => 'platform',
                        'date' => date('Y-m-d'),
                        'code' => $map['code'],
                        'amount' => bcsub(strval($user->getAttr('rebate_total')), strval($user->getAttr('rebate_usable')), 2),
                        'status' => 5,
                        'remark' => $user->getAttr('rebate_usable_desc'),
                        'charge_rate' => 0,
                        'charge_amount' => '0.00',
                        'change_time' => date('Y-m-d H:i:s'),
                        'change_desc' => '已经处理完成',
                    ]);
                    $user->save([
                        'unid' => $account->getUnid(),
                        'rebate_total_code' => $rebate->getAttr('code'),
                        'rebate_usable_code' => $transfer->getAttr('code'),
                    ]);
                    // 更新代理身份及返佣记录
                    UserOrder::entry($user->getAttr('unid'));
                    UserRebate::recount($user->getAttr('unid'));
                });
            } catch (\Exception $exception) {
                trace_file($exception);
                throw new Exception($exception->getMessage());
            }
        }
        throw new Exception('无效的用户记录！');
    }

    /**
     * 取消账号及返佣.
     * @param mixed $user
     * @throws Exception
     */
    public static function cancel($user)
    {
        if (($user = self::withModel($user))->isExists()) {
            try {
                Library::$sapp->db->transaction(function () use ($user) {
                    // 取消返佣记录
                    if (!empty($rCode = $user->getAttr('rebate_total_code'))) {
                        $map = ['code' => $rCode, 'unid' => $user->getAttr('unid')];
                        PluginWemallUserRebate::mk()->where($map)->delete();
                    }
                    // 创建提现记录
                    if (!empty($tCode = $user->getAttr('rebate_usable_code'))) {
                        $map = ['code' => $tCode, 'unid' => $user->getAttr('unid')];
                        PluginWemallUserTransfer::mk()->where($map)->delete();
                    }
                    // 更新代理身份及返佣记录
                    UserOrder::entry($user->getAttr('unid'));
                    UserRebate::recount($user->getAttr('unid'));
                });
            } catch (\Exception $exception) {
                throw new Exception($exception->getMessage());
            }
        } else {
            throw new Exception('无效的用户记录！');
        }
    }

    /**
     * 标准化模型.
     * @param int|PluginWemallUserCreate|string $model
     * @throws Exception
     */
    public static function withModel($model): PluginWemallUserCreate
    {
        if (is_numeric($model)) {
            return PluginWemallUserCreate::mk()->findOrEmpty($model);
        }
        if ($model instanceof PluginWemallUserCreate) {
            return $model;
        }
        throw new Exception('无效参数类型！');
    }
}
