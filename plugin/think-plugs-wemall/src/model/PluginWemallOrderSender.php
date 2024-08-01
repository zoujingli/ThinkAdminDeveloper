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

use think\model\relation\HasOne;

/**
 * 商城订单发货模型
 * @class PluginWemallOrderSender
 * @package plugin\wemall\model
 */
class PluginWemallOrderSender extends AbsUser
{
    /**
     * 关联订单数据
     * @return \think\model\relation\HasOne
     */
    public function main(): HasOne
    {
        return $this->hasOne(PluginWemallOrder::class, 'order_no', 'order_no')->with(['items']);
    }

    /**
     * 设置发货时间
     * @param mixed $value
     * @return string
     */
    public function setExpressTimeAttr($value): string
    {
        return $this->setCreateTimeAttr($value);
    }

    /**
     * 获取发货时间
     * @param mixed $value
     * @return string
     */
    public function getExpressTimeAttr($value): string
    {
        return $this->getCreateTimeAttr($value);
    }
}