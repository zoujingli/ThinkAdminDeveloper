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

namespace plugin\wemall\service;

use plugin\account\model\PluginAccountUser;
use plugin\wemall\model\PluginWemallConfigAgent;
use plugin\wemall\model\PluginWemallConfigLevel;
use plugin\wemall\model\PluginWemallOrder;
use plugin\wemall\model\PluginWemallUserRelation;
use think\admin\Exception;
use think\admin\Library;

/**
 * 用户代理数据服务
 * @class UserAgent
 * @package plugin\wemall\service
 */
abstract class UserAgent
{

    /**
     * 同步计算代理等级
     * @param integer|PluginWemallUserRelation $unid 指定用户UID
     * @param boolean $parent 同步计算上级
     * @param ?string $orderNo 升级触发订单
     * @return PluginWemallUserRelation
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function upgrade($unid, bool $parent = true, ?string $orderNo = null): PluginWemallUserRelation
    {
        [$rela, $unid] = PluginWemallUserRelation::withRelation($unid);
        if ($rela->isEmpty()) throw new Exception("无效用户账号！");
        // 筛选会员等级
        $mLevels = PluginWemallConfigLevel::mk()->where(['status' => 1, 'upgrade_team' => 1])->column('number');
        // 统计团队数据
        $model = PluginWemallUserRelation::mk()->where(['level_code' => $mLevels]);
        $teamsTotal = (clone $model)->whereLike('path', "{$rela->getAttr('path')}%")->count();
        $teamsDirect = (clone $model)->where(['puid1' => $unid])->count();
        $teamsIndirect = (clone $model)->where(['puid2' => $unid])->count();
        // 团队总金额(不含自己)
        $relaSql = (clone $model)->whereLike('path', "{$rela->getAttr('path')}%")->field('unid')->buildSql();
        $amountTotal = PluginWemallOrder::mk()->whereRaw("unid in {$relaSql}")->where("unid={$unid} and status>=4")->sum('amount_total');
        // 直接及间接团队金额(不含自己)
        $relaSql = (clone $model)->where(['puid1' => $unid])->field('unid')->buildSql();
        $amountDirect = PluginWemallOrder::mk()->whereRaw("puid1 in {$relaSql}")->where("unid={$unid} and status>=4")->sum('amount_total');
        $amountIndirect = PluginWemallOrder::mk()->whereRaw("puid2 in {$relaSql}")->where("unid={$unid} and status>=4")->sum('amount_total');
        // 通过订单升级等级
        $map = ['unid' => $unid, 'payment_status' => 1];
        $tmpCode = PluginWemallOrder::mk()->where($map)->where('status', '>', 4)->max('level_agent');
        // 动态计算会员等级
        [$levelName, $levelCode, $levelCurr] = ['会员用户', 0, intval($rela->getAttr('agent_level_code'))];
        foreach (PluginWemallConfigAgent::mk()->where(['status' => 1])->order('number desc')->select()->toArray() as $item) {
            if ($item['number'] === intval($tmpCode) || empty($item['number'])) {
                [$levelName, $levelCode] = [$item['name'], $item['number']];
                break;
            }
            $extra = $item['extra'] ?? [];
            $l1 = !empty($extra['teams_total_status']) && ($extra['teams_total_number'] ?? 0.01) <= $teamsTotal;
            $l2 = !empty($extra['teams_direct_status']) && ($extra['teams_direct_number'] ?? 0.01) <= $teamsDirect;
            $l3 = !empty($extra['teams_indirect_status']) && ($extra['teams_indirect_number'] ?? 0.01) <= $teamsIndirect;
            $l4 = !empty($extra['amount_total_status']) && ($extra['amount_total_number'] ?? 0.01) <= $amountTotal;
            $l5 = !empty($extra['amount_direct_status']) && ($extra['amount_direct_number'] ?? 0.01) <= $amountDirect;
            $l6 = !empty($extra['amount_indirect_status']) && ($extra['amount_indirect_number'] ?? 0.01) <= $amountIndirect;
            if (
                ($item['upgrade_type'] == 0 && ($l1 || $l2 || $l3 || $l4 || $l5 || $l6)) /* 满足任何条件 */
                ||
                ($item['upgrade_type'] == 1 && ($l1 && $l2 && $l3 && $l4 && $l5 || $l6)) /* 满足所有条件 */
            ) {
                [$levelName, $levelCode] = [$item['name'], $item['number']];
                break;
            }
        }
        // 收集团队数据
        $extra = [
            'teams_users_total'     => $teamsTotal,
            'teams_users_direct'    => $teamsDirect,
            'teams_users_indirect'  => $teamsIndirect,
            'teams_amount_total'    => $amountTotal,
            'teams_amount_direct'   => $amountDirect,
            'teams_amount_indirect' => $amountIndirect,
        ];
        if (!empty($orderNo)) $extra['agent_level_order'] = $orderNo;
        if ($levelCode !== $levelCurr) $extra['agent_level_change'] = date('Y-m-d H:i:s');
        // 更新用户扩展数据
        $user = PluginAccountUser::mk()->findOrEmpty($unid);
        $user->isExists() && $user->save(['extra' => array_merge($user->getAttr('extra'), $extra)]);
        // 代理等级数据
        $rela->save(['agent_level_name' => $levelName, 'agent_level_code' => $levelCode]);
        $levelCurr < $levelCode && Library::$sapp->event->trigger('PluginWemallUpgradeAgent', [
            'unid' => $unid, 'order_no' => $orderNo, 'agent_code_old' => $levelCurr, 'agent_code_new' => $levelCode,
        ]);
        if ($parent && empty($rela->getAttr('puids')) && $rela->getAttr('puid1') > 0) {
            static::upgrade(intval($rela->getAttr('puid1')));
        }
        return $rela;
    }
}