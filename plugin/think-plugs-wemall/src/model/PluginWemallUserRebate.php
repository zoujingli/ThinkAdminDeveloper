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

namespace plugin\wemall\model;

use plugin\account\model\PluginAccountUser;
use plugin\wemall\service\UserRebate;
use think\model\relation\HasOne;

/**
 * 代理返佣数据.
 *
 * @property float $amount 奖励数量
 * @property float $order_amount 订单金额
 * @property string $delete_time 删除时间
 * @property int $id
 * @property int $layer 上级层级
 * @property int $order_unid 订单用户
 * @property int $status 生效状态(0未生效,1已生效)
 * @property int $unid 用户UNID
 * @property string $code 奖励编号
 * @property string $confirm_time 到账时间
 * @property string $create_time 创建时间
 * @property string $date 奖励日期
 * @property string $hash 唯一编号
 * @property string $name 奖励名称
 * @property string $order_no 订单单号
 * @property string $remark 奖励描述
 * @property string $type 奖励类型
 * @property string $update_time 更新时间
 * @property PluginAccountUser $ouser
 * @class PluginWemallUserRebate
 */
class PluginWemallUserRebate extends AbsUser
{
    /**
     * 关联订单用户.
     */
    public function ouser(): HasOne
    {
        return $this->hasOne(PluginAccountUser::class, 'id', 'order_unid');
    }

    /**
     * 数据转换格式.
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        if (isset($data['type'])) {
            $map = ['platform' => '平台发放'];
            $data['type_name'] = $map[$data['type']] ?? (UserRebate::prizes[$data['type']] ?? $data['type']);
        }
        return $data;
    }
}
