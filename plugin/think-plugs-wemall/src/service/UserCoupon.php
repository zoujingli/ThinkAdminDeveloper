<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
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

use plugin\wemall\model\PluginWemallConfigCoupon;
use plugin\wemall\model\PluginWemallUserCoupon;
use plugin\wemall\model\PluginWemallUserRelation;
use think\admin\Exception;
use think\admin\extend\CodeExtend;

/**
 * 用户优惠券管理
 * @class UserCoupon
 * @package plugin\wemall\service
 */
abstract class UserCoupon
{
    /**
     * @param int|PluginWemallUserRelation $unid
     * @param integer $coid 卡券编号
     * @return \plugin\wemall\model\PluginWemallUserCoupon
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function create($unid, int $coid): PluginWemallUserCoupon
    {
        [$rela, $unid] = PluginWemallUserRelation::withRelation($unid);
        // 检查卡券
        $where = ['id' => $coid, 'status' => 1, 'deleted' => 0];
        $coupon = PluginWemallConfigCoupon::mk()->where($where)->findOrEmpty();
        if ($coupon->isEmpty()) throw new Exception('无效卡券');
        if ($coupon->getAttr('total_sales') >= $coupon->getAttr('total_stock')) {
            throw new Exception('已领取完！');
        }
        // 领取等级检查
        $limitLevels = $coupon->getAttr('limit_levels');
        if (!(in_array('-', $limitLevels) || in_array($rela->getAttr('level_code'), $limitLevels))) {
            throw new Exception('无权限领取！');
        }
        // 领取数量检查
        if (($limitTimes = $coupon->getAttr('limit_times')) > 0) {
            $map = ['deleted' => 0, 'unid' => $unid, 'coid' => $coupon->getAttr('id')];
            if (PluginWemallUserCoupon::mk()->where($map)->count() > $limitTimes) {
                throw new Exception('已超出领取数量！');
            }
        }
        $data = ['unid' => $unid, 'coid' => $coid, 'type' => $coupon->getAttr('type')];
        // 有效时间处理
        if (($expireDays = $coupon->getAttr('expire_days')) > 0) {
            $data['expire'] = time() + $expireDays * 3600;
            $data['expire_time'] = date('Y-m-d H:i:s', $data['expire']);
        }
        do $data['code'] = $code = CodeExtend::uniqidNumber(16, 'C');
        while (($model = PluginWemallUserCoupon::mk()->where(['code' => $code])->findOrEmpty())->isExists());
        // 保存及返回模型
        if ($model->save($data) && self::recount($coupon)) return $model;
        throw new Exception('领取卡券失败！');
    }

    /**
     * 重置卡券统计
     * @param int|PluginWemallConfigCoupon $coid
     * @return boolean
     * @throws \think\admin\Exception
     */
    public static function recount($coid): bool
    {
        $model = self::withModel($coid);
        $where = ['coid' => $model->getAttr('id'), 'deleted' => 0];
        $field = ['sum(used)' => 'total_used', 'count(1)' => 'total_sales'];
        $total = PluginWemallUserCoupon::mk()->field($field)->where($where)->findOrEmpty()->toArray();
        return $model->save($total);
    }

    /**
     * 恢复优惠券
     * @param string $code
     * @return \plugin\wemall\model\PluginWemallUserCoupon
     */
    public static function resume(string $code): PluginWemallUserCoupon
    {
        $coupon = PluginWemallUserCoupon::mk()->where(['code' => $code, 'status' => 2])->findOrEmpty();
        if ($coupon->isExists()) $coupon->save(['used' => 0, 'used_time' => null, 'status' => 1]);
        return $coupon;
    }

    /**
     * 确认使用优惠券
     * @param string $code
     * @return \plugin\wemall\model\PluginWemallUserCoupon
     * @throws \think\admin\Exception
     */
    public static function confirm(string $code): PluginWemallUserCoupon
    {
        $map = ['code' => $code, 'status' => 1];
        if (($coupon = PluginWemallUserCoupon::mk()->where($map)->findOrEmpty())->isExists()) {
            if ($coupon->getAttr('expire') > 0 && $coupon->getAttr('expire') < time()) {
                $coupon->save(['status' => 3, 'status_time' => date('Y-m-d H:i:s'), 'status_desc' => '优惠券已过期！']);
                throw new Exception("优惠券已过期");
            }
            $coupon->save(['status' => 2, 'status_time' => date("Y-m-d H:i:s"), 'status_desc' => '优惠券已使用！']);
            return $coupon;
        } else {
            throw new Exception("优惠券不可用！");
        }
    }

    /**
     * 获取优惠券模型
     * @param int|PluginWemallConfigCoupon $model
     * @return PluginWemallConfigCoupon
     * @throws \think\admin\Exception
     */
    public static function withModel($model): PluginWemallConfigCoupon
    {
        if (is_numeric($model)) {
            $model = PluginWemallConfigCoupon::mk()->where(['id' => $model])->findOrEmpty();
        }
        if ($model instanceof PluginWemallConfigCoupon) {
            if ($model->isExists()) return $model;
            throw new Exception("记录不存在！");
        } else {
            throw new Exception('无效参数类型！');
        }
    }
}