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

namespace plugin\wemall\model;

use plugin\account\model\PluginAccountUser;
use plugin\wemall\service\UserRebate;
use think\model\relation\HasOne;

/**
 * 用户返佣数据
 * @class PluginWemallUserRebate
 * @package plugin\wemall\model
 */
class PluginWemallUserRebate extends AbsUser
{
    /**
     * 关联订单用户
     * @return \think\model\relation\HasOne
     */
    public function ouser(): HasOne
    {
        return $this->hasOne(PluginAccountUser::class, 'id', 'order_unid');
    }

    /**
     * 数据转换格式
     * @return array
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