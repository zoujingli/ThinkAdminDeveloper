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
use plugin\payment\service\Balance;
use plugin\payment\service\Integral;
use plugin\wemall\model\PluginWemallConfigLevel;
use plugin\wemall\model\PluginWemallOrder;
use plugin\wemall\model\PluginWemallUserRelation;
use think\admin\Exception;
use think\admin\Library;

/**
 * 会员等级升级服务
 * @class UserUpgrade
 * @package plugin\wemall\service
 */
abstract class UserUpgrade
{
    /**
     * 读取用户代理编号
     * @param integer|PluginWemallUserRelation $unid 会员用户
     * @param integer $puid 代理用户
     * @return array
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function withAgent($unid, int $puid): array
    {
        [$rela, $unid] = PluginWemallUserRelation::withRelation($unid);
        // 绑定代理数据
        $puid1 = $rela['puid1'] ?? 0; // 上1级代理
        $puid2 = $rela['puid2'] ?? 0; // 上2级代理
        $puid3 = $rela['puid3'] ?? 0; // 上3级代理
        if (empty($rela['puids']) && $puid > 0) {
            // 创建临时绑定
            $rela = self::bindAgent($unid, $puid, 0);
            $puid1 = $rela->getAttr('puid1') ?: 0; // 上1级代理
            $puid2 = $rela->getAttr('puid2') ?: 0; // 上2级代理
            $puid3 = $rela->getAttr('puid3') ?: 0; // 上3级代理
        }
        return ['unid' => $unid, 'puid1' => $puid1, 'puid2' => $puid2, 'puid3' => $puid3];
    }

    /**
     * 尝试绑定上级代理
     * @param integer|PluginWemallUserRelation $unid 用户 UNID
     * @param integer $puid 代理 UNID
     * @param integer $mode 操作类型（0临时绑定, 1永久绑定, 2强行绑定）
     * @return \plugin\wemall\model\PluginWemallUserRelation
     * @throws \think\admin\Exception
     */
    public static function bindAgent($unid, int $puid = 0, int $mode = 1): PluginWemallUserRelation
    {
        try {
            [$rela, $unid] = PluginWemallUserRelation::withRelation($unid);
            // 已经绑定不允许替换原代理信息
            $puid1 = intval($rela->getAttr('puid1'));
            if ($puid1 > 0 && $rela->getAttr('puids') > 0) {
                if ($puid1 !== $puid) throw new Exception('已绑定代理！');
            }
            // 检查代理用户
            if (empty($puid)) $puid = $puid1;
            if (empty($puid)) throw new Exception('代理不存在！');
            if ($unid === $puid) throw new Exception('不能绑定自己！');
            // 检查上级用户
            $parent = PluginWemallUserRelation::withInit($puid);
            if (strpos($parent->getAttr('path'), ",{$unid},") !== false) {
                throw new Exception('不能绑下级！');
            }
            if (empty($parent->getAttr('entry_agent'))) {
                throw new Exception("无推广权限！");
            }
            Library::$sapp->db->transaction(function () use ($rela, $parent, $mode) {
                self::forceReplaceParent($rela, $parent, ['puids' => $mode > 0 ? 1 : 0]);
            });
            return static::upgrade($rela);
        } catch (Exception $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            throw new Exception("绑定代理失败, {$exception->getMessage()}");
        }
    }

    /**
     * 更替用户上级关系
     * @param PluginWemallUserRelation $relation
     * @param PluginWemallUserRelation $parent
     * @param array $extra 扩展数据
     * @return PluginWemallUserRelation
     */
    public static function forceReplaceParent(PluginWemallUserRelation $relation, PluginWemallUserRelation $parent, array $extra = []): PluginWemallUserRelation
    {
        $path1 = arr2str(str2arr("{$parent->getAttr('path')},{$parent->getAttr('unid')}"));
        $relation->save(array_merge([
            'path'  => $path1,
            'puid1' => $parent->getAttr('unid'),
            'puid2' => $parent->getAttr('puid1'),
            'puid3' => $parent->getAttr('puid2'),
            'layer' => substr_count($path1, ','),
        ], $extra));
        /** 更新所有下级代理 @var PluginWemallUserRelation $item */
        $path2 = arr2str(str2arr("{$relation->getAttr('path')},{$relation->getAttr('unid')}"));
        foreach (PluginWemallUserRelation::mk()->whereLike('path', "{$path2}%")->order('layer desc')->cursor() as $item) {
            $text = arr2str(str2arr("{$relation->getAttr('path')},{$relation->getAttr('unid')}"));
            $attr = array_reverse(str2arr($path3 = preg_replace("#^{$path2}#", $text, $item->getAttr('path'))));
            $item->save([
                'puid1' => $attr[0] ?? 0, 'puid2' => $attr[1] ?? 0, 'path' => $path3,
                'puid3' => $attr[2] ?? 0, 'layer' => substr_count($path3, ',')
            ]);
        }
        return $relation;
    }

    /**
     * 同步计算会员等级
     * @param integer|PluginWemallUserRelation $unid 指定用户
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
        // 订单升级等级
        $map = ['unid' => $unid, 'payment_status' => 1];
        $tmpCode = PluginWemallOrder::mk()->where($map)->where('status', '>', 4)->max('level_member');
        // 统计订单金额
        $orderAmount = PluginWemallOrder::mk()->where("unid={$unid} and status>=4")->sum('amount_total');
        // 动态计算会员等级
        [$levelName, $levelCode, $levelCurr] = ['普通用户', 0, intval($rela->getAttr('level_code'))];
        foreach (PluginWemallConfigLevel::mk()->where(['status' => 1])->order('number desc')->cursor() as $item) {
            if ($item['number'] === intval($tmpCode) || empty($item['number'])) {
                [$levelName, $levelCode] = [$item['name'], $item['number']];
                break;
            }
            $extra = $item['extra'] ?? [];
            $l1 = !empty($extra['enter_vip_status']);
            $l2 = !empty($extra['order_amount_status']) && ($extra['order_amount_number'] ?? 0.01) <= $orderAmount;
            if (
                ($item['upgrade_type'] == 0 && ($l1 || $l2)) /* 满足任何条件 */
                ||
                ($item['upgrade_type'] == 1 && ($l1 && $l2)) /* 满足所有条件 */
            ) {
                [$levelName, $levelCode] = [$item['name'], $item['number']];
                break;
            }
        }
        // 收集用户团队数据
        $extra = ['order_amount_total' => $orderAmount];
        if (!empty($orderNo)) $extra['level_order'] = $orderNo;
        if ($levelCode !== $levelCurr) $extra['level_change'] = date('Y-m-d H:i:s');
        // 更新用户扩展数据
        $user = PluginAccountUser::mk()->findOrEmpty($unid);
        $user->isExists() && $user->save(['extra' => array_merge($user->getAttr('extra'), $extra)]);
        // 会员等级数据
        $rela->save(['level_name' => $levelName, 'level_code' => $levelCode]);
        $levelCurr < $levelCode && Library::$sapp->event->trigger('PluginWemallUpgradeLevel', [
            'unid' => $unid, 'order_no' => $orderNo, 'level_code_old' => $levelCurr, 'level_code_new' => $levelCode,
        ]);
        if ($parent && empty($rela->getAttr('puids')) && $rela->getAttr('puid1') > 0) {
            static::upgrade(intval($rela->getAttr('puid1')));
        }
        return $rela;
    }

    /**
     * 同步重算用户数据
     * @param int $unid 指定用户
     * @param boolean $init 初始化用户
     * @return array
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function recount(int $unid, bool $init = false): array
    {
        $data = [];
        // 初始化用户
        if ($init) PluginWemallUserRelation::withInit($unid);
        // 重算余额 & 重算积分 & 重算行为 & 订单返佣
        Balance::recount($unid, $data) && Integral::recount($unid, $data);
        UserAction::recount($unid, $data) && UserRebate::recount($unid, $data);
        if (($user = PluginAccountUser::mk()->findOrEmpty($unid))->isExists()) {
            $user->save(['extra' => array_merge($user->getAttr('extra'), $data)]);
        }
        return $data;
    }
}